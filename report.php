<?php
// This file is part of Moodle - http://moodle.org/

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('aigradedassign', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$activity = $DB->get_record('aigradedassign', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/aigradedassign:viewallsubmissions', $context);

$PAGE->set_url('/mod/aigradedassign/report.php', ['id' => $cm->id]);
$PAGE->set_title(get_string('report', 'aigradedassign'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_cm($cm, $course, $activity);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('report', 'aigradedassign'));
$sql = "SELECT s.id, s.userid, s.status, s.attemptnumber, s.timemodified, s.timeevaluated,
               u.firstname, u.lastname, e.id AS evaluationid, e.score AS evaluationscore,
               e.feedbacktext AS assessment, e.reviewstatus
          FROM {aigradedassign_submissions} s
          JOIN {user} u ON u.id = s.userid
     LEFT JOIN {aigradedassign_evaluations} e
            ON e.submissionid = s.id
           AND e.attemptnumber = s.attemptnumber
         WHERE s.aigradedassignid = :activityid
      ORDER BY s.timemodified DESC";
$records = $DB->get_records_sql($sql, ['activityid' => $activity->id]);
$showreview = !empty($activity->requirevalidation);
foreach ($records as $record) {
    if ($record->reviewstatus === 'pending') {
        $showreview = true;
        break;
    }
}
$table = new html_table();
$table->head = [
    get_string('student', 'aigradedassign'),
    get_string('submissionstatus', 'aigradedassign'),
    get_string('submittedat', 'aigradedassign'),
    get_string('evaluatedat', 'aigradedassign'),
    get_string('grading', 'aigradedassign'),
    get_string('assessment', 'aigradedassign'),
];
if ($showreview) {
    $table->head[] = get_string('tutorreview', 'aigradedassign');
}
foreach ($records as $record) {
    $grading = $record->evaluationscore === null
        ? '-'
        : get_string('scoreoutof', 'aigradedassign', [
            'score' => format_float($record->evaluationscore, 2, true),
            'maximum' => 10,
        ]);
    $assessment = trim((string) $record->assessment) === ''
        ? '-'
        : html_writer::tag('div', s($record->assessment), ['class' => 'text-pre-wrap']);
    $row = [
        fullname($record),
        get_string('status:' . $record->status, 'aigradedassign'),
        userdate($record->timemodified),
        $record->timeevaluated ? userdate($record->timeevaluated) : '-',
        $grading,
        $assessment,
    ];
    if ($showreview) {
        if ($record->evaluationid && has_capability('mod/aigradedassign:grade', $context)) {
            $reviewurl = new moodle_url('/mod/aigradedassign/review.php', [
                'id' => $cm->id,
                'evaluationid' => $record->evaluationid,
            ]);
            $label = $record->reviewstatus === 'approved'
                ? get_string('editvalidation', 'aigradedassign')
                : get_string('reviewandvalidate', 'aigradedassign');
            $row[] = html_writer::link($reviewurl, $label, ['class' => 'btn btn-primary btn-sm']);
        } else {
            $row[] = '-';
        }
    }
    $table->data[] = $row;
}
echo html_writer::table($table);
echo $OUTPUT->footer();
