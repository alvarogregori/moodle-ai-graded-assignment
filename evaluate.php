<?php
// This file is part of Moodle - http://moodle.org/

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

$cm = get_coursemodule_from_id('aigradedassign', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$activity = $DB->get_record('aigradedassign', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/aigradedassign:viewallsubmissions', $context);
require_sesskey();

$submission = $DB->get_record('aigradedassign_submissions', [
    'aigradedassignid' => $activity->id,
    'userid' => $userid,
], '*', MUST_EXIST);

$text = trim($submission->extractedtext ?? '');
$wordcount = str_word_count($text);
$evaluation = "Local draft evaluation\n\n";
$evaluation .= "This is a placeholder evaluation generated inside Moodle without calling an AI provider.\n\n";
$evaluation .= "Submission length: {$wordcount} words.\n\n";
$evaluation .= "Next step: replace this local draft with an LLM-generated evaluation using the private rubric and example context.";

$DB->delete_records('aigradedassign_evals', ['submissionid' => $submission->id]);
$DB->insert_record('aigradedassign_evals', (object)[
    'submissionid' => $submission->id,
    'provider' => 'local',
    'model' => 'placeholder',
    'promptversion' => 'local-v1',
    'evaluationtext' => $evaluation,
    'evaluationformat' => FORMAT_PLAIN,
    'rawresponse' => null,
    'finishreason' => 'local',
    'inputtokens' => null,
    'outputtokens' => null,
    'latencyms' => null,
    'timecreated' => time(),
]);

$submission->status = 'evaluated';
$submission->timeevaluated = time();
$submission->timemodified = time();
$DB->update_record('aigradedassign_submissions', $submission);

redirect(new moodle_url('/mod/aigradedassign/view.php', ['id' => $cm->id]));
