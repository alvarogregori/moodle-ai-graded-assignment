<?php
// This file is part of Moodle - http://moodle.org/

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('aigradedassign', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$activity = $DB->get_record('aigradedassign', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/aigradedassign:submit', $context);

$url = new moodle_url('/mod/aigradedassign/submit.php', ['id' => $cm->id]);
$form = new \mod_aigradedassign\form\submission_form($url, ['cmid' => $cm->id]);
if ($form->is_cancelled()) {
    redirect(new moodle_url('/mod/aigradedassign/view.php', ['id' => $cm->id]));
}
if (!$data = $form->get_data()) {
    redirect(new moodle_url('/mod/aigradedassign/view.php', ['id' => $cm->id]));
}

$now = time();
$submission = $DB->get_record('aigradedassign_submissions', [
    'aigradedassignid' => $activity->id,
    'userid' => $USER->id,
]);
if ($submission) {
    $submission->submissiontext = trim($data->submissiontext);
    $submission->status = 'submitted';
    $submission->attemptnumber++;
    $submission->timemodified = $now;
    $submission->timeevaluated = null;
    $DB->update_record('aigradedassign_submissions', $submission);
} else {
    $submission = (object) [
        'aigradedassignid' => $activity->id,
        'userid' => $USER->id,
        'submissiontext' => trim($data->submissiontext),
        'status' => 'submitted',
        'attemptnumber' => 1,
        'timecreated' => $now,
        'timemodified' => $now,
    ];
    $submission->id = $DB->insert_record('aigradedassign_submissions', $submission);
}

try {
    $service = new \mod_aigradedassign\local\evaluation_service();
    $service->evaluate($activity, $submission);
} catch (\Throwable $exception) {
    redirect(
        new moodle_url('/mod/aigradedassign/view.php', ['id' => $cm->id]),
        get_string('evaluationfailed', 'aigradedassign', $exception->getMessage()),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}
$submission->status = 'evaluated';
$submission->timeevaluated = time();
$submission->timemodified = $submission->timeevaluated;
$DB->update_record('aigradedassign_submissions', $submission);

aigradedassign_update_grades($activity, $USER->id);
$completion = new completion_info($course);
$completion->update_state($cm, COMPLETION_COMPLETE, $USER->id);
redirect(
    new moodle_url('/mod/aigradedassign/view.php', ['id' => $cm->id]),
    get_string('submissionsaved', 'aigradedassign'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
