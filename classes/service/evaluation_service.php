<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_aigradedassign\service;

use mod_aigradedassign\ai\anthropic_client;
use mod_aigradedassign\ai\evaluation_request;
use mod_aigradedassign\ai\mistral_client;
use mod_aigradedassign\ai\openai_client;
use mod_aigradedassign\ai\provider_interface;
use mod_aigradedassign\local\prompt_builder;
use mod_aigradedassign\local\text_extractor;

defined('MOODLE_INTERNAL') || die();

class evaluation_service {
    public function evaluate_submission(int $submissionid, int $cmid): void {
        global $DB;

        $submission = $DB->get_record('aigradedassign_submissions', ['id' => $submissionid], '*', MUST_EXIST);
        $activity = $DB->get_record('aigradedassign', ['id' => $submission->aigradedassignid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_id('aigradedassign', $cmid, 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $context = \context_module::instance($cm->id);

        try {
            $this->set_status($submission, 'evaluating');

            $file = $this->get_submission_file($context, $submission->id);
            $extractor = new text_extractor();
            $submissiontext = $extractor->extract($file);
            $submissiontext = $this->limit_text($submissiontext, (int)$activity->maxchars);

            $submission->extractedtext = $submissiontext;
            $submission->extractedtextsha256 = hash('sha256', $submissiontext);
            $submission->timemodified = time();
            $DB->update_record('aigradedassign_submissions', $submission);

            $rubricfile = $this->get_area_file($context, 'rubric', 0);
            $rubrictext = $this->limit_text($extractor->extract($rubricfile), (int)$activity->maxchars);

            $examples = $this->load_examples_with_files($activity->id, $context, $extractor, (int)$activity->maxchars);
            $messages = (new prompt_builder())->build($activity, $rubrictext, $examples, $submissiontext);

            $client = $this->get_provider_client($activity->provider);
            $request = new evaluation_request(
                $activity->model ?: $this->default_model($activity->provider),
                (float)$activity->temperature,
                (int)$activity->maxtokens,
                $messages
            );
            $response = $client->evaluate($request);

            $DB->delete_records('aigradedassign_evals', ['submissionid' => $submission->id]);
            $DB->insert_record('aigradedassign_evals', (object)[
                'submissionid' => $submission->id,
                'provider' => $activity->provider,
                'model' => $request->model,
                'promptversion' => 'v1',
                'evaluationtext' => $response->text,
                'evaluationformat' => FORMAT_MARKDOWN,
                'rawresponse' => $response->rawresponse,
                'finishreason' => $response->finishreason,
                'inputtokens' => $response->inputtokens,
                'outputtokens' => $response->outputtokens,
                'latencyms' => $response->latencyms,
                'timecreated' => time(),
            ]);

            $submission->status = 'evaluated';
            $submission->timeevaluated = time();
            $submission->timemodified = time();
            $submission->errorcode = null;
            $submission->errormessage = null;
            $DB->update_record('aigradedassign_submissions', $submission);

            $completion = new \completion_info($course);
            if ($completion->is_enabled($cm)) {
                $completion->update_state($cm, COMPLETION_COMPLETE, $submission->userid);
            }
        } catch (\Throwable $e) {
            $submission->status = 'failed';
            $submission->errorcode = substr(get_class($e), 0, 60);
            $submission->errormessage = $e->getMessage();
            $submission->timemodified = time();
            $DB->update_record('aigradedassign_submissions', $submission);
            throw $e;
        }
    }

    private function set_status(\stdClass $submission, string $status): void {
        global $DB;

        $submission->status = $status;
        $submission->timemodified = time();
        $DB->update_record('aigradedassign_submissions', $submission);
    }

    private function get_submission_file(\context_module $context, int $submissionid): \stored_file {
        return $this->get_area_file($context, 'submission', $submissionid);
    }

    private function get_area_file(\context_module $context, string $filearea, int $itemid): \stored_file {
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_aigradedassign', $filearea, $itemid, 'id', false);
        if (!$files) {
            throw new \moodle_exception('missingrequiredfile', 'aigradedassign', '', $filearea);
        }
        return reset($files);
    }

    private function load_examples_with_files(
        int $activityid,
        \context_module $context,
        text_extractor $extractor,
        int $maxchars
    ): array {
        global $DB;

        $examples = $DB->get_records('aigradedassign_examples', ['aigradedassignid' => $activityid], 'sortorder ASC');
        foreach ($examples as $example) {
            $file = $this->get_area_file($context, 'example_submission', (int)$example->id);
            $example->sampletext = $this->limit_text($extractor->extract($file), $maxchars);
        }
        return $examples;
    }

    private function limit_text(string $text, int $maxchars): string {
        if ($maxchars <= 0 || \core_text::strlen($text) <= $maxchars) {
            return $text;
        }
        return \core_text::substr($text, 0, $maxchars);
    }

    private function get_provider_client(string $provider): provider_interface {
        $apikey = get_config('mod_aigradedassign', $provider . 'apikey');
        if (empty($apikey)) {
            throw new \moodle_exception('missingapikey', 'aigradedassign', '', $provider);
        }

        return match ($provider) {
            'mistral' => new mistral_client($apikey),
            'openai' => new openai_client($apikey),
            'anthropic' => new anthropic_client($apikey),
            default => throw new \moodle_exception('unknownprovider', 'aigradedassign', '', $provider),
        };
    }

    private function default_model(string $provider): string {
        return match ($provider) {
            'openai' => 'gpt-5-mini',
            'anthropic' => 'claude-sonnet-4-20250514',
            default => 'mistral-large-latest',
        };
    }
}
