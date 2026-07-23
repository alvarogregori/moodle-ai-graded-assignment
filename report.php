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
$PAGE->set_activity_record($activity);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('report', 'aigradedassign'));
$sql = "SELECT s.*, u.firstname, u.lastname
          FROM {aigradedassign_submissions} s
          JOIN {user} u ON u.id = s.userid
         WHERE s.aigradedassignid = :activityid
      ORDER BY s.timemodified DESC";
$records = $DB->get_records_sql($sql, ['activityid' => $activity->id]);
$table = new html_table();
$table->head = [
    get_string('student', 'aigradedassign'),
    get_string('submissionstatus', 'aigradedassign'),
    get_string('submittedat', 'aigradedassign'),
    get_string('evaluatedat', 'aigradedassign'),
];
foreach ($records as $record) {
    $table->data[] = [
        fullname($record),
        get_string('status:' . $record->status, 'aigradedassign'),
        userdate($record->timemodified),
        $record->timeevaluated ? userdate($record->timeevaluated) : '-',
    ];
}
echo html_writer::table($table);
echo $OUTPUT->footer();
