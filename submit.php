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
require_sesskey();

$form = new \mod_aigradedassign\form\submission_form(
    new moodle_url('/mod/aigradedassign/submit.php'),
    ['cmid' => $cm->id]
);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/mod/aigradedassign/view.php', ['id' => $cm->id]));
}

$data = $form->get_data();
if (!$data) {
    redirect(new moodle_url('/mod/aigradedassign/view.php', ['id' => $cm->id]));
}

$submissiontext = trim($data->submissiontext ?? '');
$now = time();
$existing = $DB->get_record('aigradedassign_submissions', [
    'aigradedassignid' => $activity->id,
    'userid' => $USER->id,
], '*', IGNORE_MULTIPLE);

if ($existing) {
    $submission = $existing;
    $submission->status = 'submitted';
    $submission->attemptnumber = (int)$existing->attemptnumber + 1;
    $submission->extractedtext = $submissiontext;
    $submission->extractedtextsha256 = hash('sha256', $submissiontext);
    $submission->errorcode = null;
    $submission->errormessage = null;
    $submission->timemodified = $now;
    $submission->timesubmitted = $now;
    $submission->timeevaluated = null;
    $DB->update_record('aigradedassign_submissions', $submission);
} else {
    $DB->insert_record('aigradedassign_submissions', (object)[
        'aigradedassignid' => $activity->id,
        'userid' => $USER->id,
        'status' => 'submitted',
        'attemptnumber' => 1,
        'extractedtext' => $submissiontext,
        'extractedtextsha256' => hash('sha256', $submissiontext),
        'errorcode' => null,
        'errormessage' => null,
        'timecreated' => $now,
        'timemodified' => $now,
        'timesubmitted' => $now,
        'timeevaluated' => null,
    ]);
}

redirect(new moodle_url('/mod/aigradedassign/view.php', ['id' => $cm->id]));
