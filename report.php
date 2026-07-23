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
               u.firstname, u.lastname, e.score AS evaluationscore, e.feedbacktext AS assessment
          FROM {aigradedassign_submissions} s
          JOIN {user} u ON u.id = s.userid
     LEFT JOIN {aigradedassign_evaluations} e
            ON e.submissionid = s.id
           AND e.attemptnumber = s.attemptnumber
         WHERE s.aigradedassignid = :activityid
      ORDER BY s.timemodified DESC";
$records = $DB->get_records_sql($sql, ['activityid' => $activity->id]);
$table = new html_table();
$table->head = [
    get_string('student', 'aigradedassign'),
    get_string('submissionstatus', 'aigradedassign'),
    get_string('submittedat', 'aigradedassign'),
    get_string('evaluatedat', 'aigradedassign'),
    get_string('grading', 'aigradedassign'),
    get_string('assessment', 'aigradedassign'),
];
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
    $table->data[] = [
        fullname($record),
        get_string('status:' . $record->status, 'aigradedassign'),
        userdate($record->timemodified),
        $record->timeevaluated ? userdate($record->timeevaluated) : '-',
        $grading,
        $assessment,
    ];
}
echo html_writer::table($table);
echo $OUTPUT->footer();
