<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_aigradedassign\local;

use mod_aigradedassign\ai\evaluation_input;
use mod_aigradedassign\ai\provider_factory;

/**
 * Application service that evaluates and persists a submission.
 *
 * @package    mod_aigradedassign
 * @copyright  2026 Alvaro Gregori
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class evaluation_service {
    /**
     * Evaluates a submission and stores the result.
     *
     * @param stdClass $activity Activity instance.
     * @param stdClass $submission Submission record.
     * @return stdClass Evaluation record.
     */
    public function evaluate(\stdClass $activity, \stdClass $submission): \stdClass {
        global $DB;

        $provider = provider_factory::create($activity->provider ?: 'mock');
        $examples = [];
        for ($number = 1; $number <= 3; $number++) {
            $suffix = $number === 1 ? '' : (string) $number;
            $text = trim((string) ($activity->{'exampletext' . $suffix} ?? ''));
            $feedback = trim((string) ($activity->{'examplefeedback' . $suffix} ?? ''));
            if ($text !== '' && $feedback !== '') {
                $examples[] = ['submission' => $text, 'assessment' => $feedback];
            }
        }
        $result = $provider->evaluate(new evaluation_input(
            \content_to_text($activity->intro, $activity->introformat),
            $activity->rubrictext,
            $examples,
            $submission->submissiontext,
        ));
        $record = (object) [
            'submissionid' => $submission->id,
            'attemptnumber' => $submission->attemptnumber,
            'provider' => $result->provider,
            'model' => $result->model,
            'score' => $result->score,
            'feedbacktext' => $result->feedback,
            'reviewstatus' => !empty($activity->requirevalidation) ? 'pending' : 'approved',
            'timecreated' => time(),
        ];
        $record->id = $DB->insert_record('aigradedassign_evaluations', $record);
        return $record;
    }
}
