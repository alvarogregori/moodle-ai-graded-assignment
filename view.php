<?php
// This file is part of Moodle - http://moodle.org/

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('aigradedassign', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$activity = $DB->get_record('aigradedassign', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/aigradedassign:view', $context);

$PAGE->set_url('/mod/aigradedassign/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($activity->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_cm($cm, $course, $activity);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($activity->name));
if (trim((string) $activity->intro) !== '') {
    echo $OUTPUT->box(format_module_intro('aigradedassign', $activity, $cm->id), 'generalbox mod_introbox');
}

if (has_capability('mod/aigradedassign:viewallsubmissions', $context)) {
    echo $OUTPUT->notification(get_string('teacherprivatecontext', 'aigradedassign'), 'info');
    $reporturl = new moodle_url('/mod/aigradedassign/report.php', ['id' => $cm->id]);
    echo $OUTPUT->single_button($reporturl, get_string('viewreport', 'aigradedassign'));
} else if (has_capability('mod/aigradedassign:submit', $context)) {
    $submission = $DB->get_record('aigradedassign_submissions', [
        'aigradedassignid' => $activity->id,
        'userid' => $USER->id,
    ]);
    if ($submission) {
        echo $OUTPUT->heading(get_string('yoursubmission', 'aigradedassign'), 3);
        echo html_writer::tag('div', s($submission->submissiontext), ['class' => 'alert alert-light text-pre-wrap']);
        $evaluation = $DB->get_record('aigradedassign_evaluations', [
            'submissionid' => $submission->id,
            'attemptnumber' => $submission->attemptnumber,
        ]);
        if ($evaluation && has_capability('mod/aigradedassign:viewownfeedback', $context)) {
            echo $OUTPUT->heading(get_string('evaluation', 'aigradedassign'), 3);
            echo html_writer::tag('div', s($evaluation->feedbacktext), ['class' => 'alert alert-success text-pre-wrap']);
        }
    }
    $form = new \mod_aigradedassign\form\submission_form(
        new moodle_url('/mod/aigradedassign/submit.php', ['id' => $cm->id]),
        ['cmid' => $cm->id]
    );
    if ($submission) {
        $form->set_data(['submissiontext' => $submission->submissiontext, 'id' => $cm->id]);
    }
    $form->display();
}
echo $OUTPUT->footer();
