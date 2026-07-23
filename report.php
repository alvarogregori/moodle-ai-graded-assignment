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

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($activity->name) . ': ' . get_string('report', 'aigradedassign'));

$sql = "SELECT s.*, u.firstname, u.lastname, u.email
          FROM {aigradedassign_submissions} s
          JOIN {user} u ON u.id = s.userid
         WHERE s.aigradedassignid = :activityid
      ORDER BY s.timemodified DESC";
$submissions = $DB->get_records_sql($sql, ['activityid' => $activity->id]);

$table = new html_table();
$table->head = [
    get_string('student', 'aigradedassign'),
    get_string('submissionstatus', 'aigradedassign'),
    get_string('submittedat', 'aigradedassign'),
    get_string('evaluatedat', 'aigradedassign'),
];

foreach ($submissions as $submission) {
    $user = (object)[
        'id' => $submission->userid,
        'firstname' => $submission->firstname,
        'lastname' => $submission->lastname,
        'email' => $submission->email,
    ];
    $table->data[] = [
        fullname($user),
        get_string('status:' . $submission->status, 'aigradedassign'),
        $submission->timesubmitted ? userdate($submission->timesubmitted) : '-',
        $submission->timeevaluated ? userdate($submission->timeevaluated) : '-',
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
