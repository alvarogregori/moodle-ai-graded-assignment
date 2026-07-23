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
$PAGE->set_context($context);
$PAGE->set_cm($cm, $course, $activity);
$PAGE->set_pagelayout('popup');
$PAGE->set_title(format_string($activity->name));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($activity->name));

if (trim($activity->intro ?? '') !== '') {
    echo html_writer::tag('div', format_module_intro('aigradedassign', $activity, $cm->id), [
        'class' => 'generalbox mod_introbox',
    ]);
}

if (has_capability('mod/aigradedassign:viewallsubmissions', $context)) {
    echo $OUTPUT->heading(get_string('evaluationcontext', 'aigradedassign'), 3);

    echo $OUTPUT->heading(get_string('rubric', 'aigradedassign'), 4);
    echo html_writer::tag('pre', s($activity->rubrictext ?? ''), [
        'class' => 'generalbox',
        'style' => 'white-space: pre-wrap;',
    ]);

    $example = $DB->get_record('aigradedassign_examples', [
        'aigradedassignid' => $activity->id,
        'sortorder' => 1,
    ]);

    if ($example) {
        echo $OUTPUT->heading(get_string('examplesampleplain', 'aigradedassign'), 4);
        echo html_writer::tag('pre', s($example->sampletext), [
            'class' => 'generalbox',
            'style' => 'white-space: pre-wrap;',
        ]);

        echo $OUTPUT->heading(get_string('exampleevaluationplain', 'aigradedassign'), 4);
        echo html_writer::tag('pre', s($example->evaluationtext), [
            'class' => 'generalbox',
            'style' => 'white-space: pre-wrap;',
        ]);
    }
}

$submission = $DB->get_record('aigradedassign_submissions', [
    'aigradedassignid' => $activity->id,
    'userid' => $USER->id,
], '*', IGNORE_MULTIPLE);

if ($submission) {
    echo $OUTPUT->heading(get_string('yoursubmission', 'aigradedassign'), 3);
    echo html_writer::tag('pre', s($submission->extractedtext ?? ''), [
        'class' => 'generalbox',
        'style' => 'white-space: pre-wrap;',
    ]);

    if (has_capability('mod/aigradedassign:viewallsubmissions', $context)) {
        $evaluateurl = new moodle_url('/mod/aigradedassign/evaluate.php', [
            'id' => $cm->id,
            'userid' => $submission->userid,
            'sesskey' => sesskey(),
        ]);
        echo html_writer::link($evaluateurl, get_string('evaluatesubmission', 'aigradedassign'), [
            'class' => 'btn btn-primary mb-3',
        ]);
    }

    $evaluation = $DB->get_record('aigradedassign_evals', ['submissionid' => $submission->id], '*', IGNORE_MULTIPLE);
    if ($evaluation) {
        echo $OUTPUT->heading(get_string('evaluation', 'aigradedassign'), 3);
        echo html_writer::tag('pre', s($evaluation->evaluationtext ?? ''), [
            'class' => 'generalbox',
            'style' => 'white-space: pre-wrap;',
        ]);
    }
} else if (has_capability('mod/aigradedassign:submit', $context)) {
    echo $OUTPUT->heading(get_string('submitwork', 'aigradedassign'), 3);
    $form = new \mod_aigradedassign\form\submission_form(
        new moodle_url('/mod/aigradedassign/submit.php'),
        ['cmid' => $cm->id]
    );
    $form->display();
}

echo $OUTPUT->footer();
