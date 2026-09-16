<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Lists all AI Graded Assignment activities in a course.
 *
 * @package    mod_aigradedassign
 * @copyright  2026 Alvaro Gregori
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

require_course_login($course);

$PAGE->set_url('/mod/aigradedassign/index.php', ['id' => $course->id]);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('modulenameplural', 'aigradedassign'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->add(get_string('modulenameplural', 'aigradedassign'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'aigradedassign'));

$activities = get_all_instances_in_course('aigradedassign', $course);
if (!$activities) {
    echo $OUTPUT->notification(get_string('noactivities', 'aigradedassign'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';
$table->head = [
    get_string('name'),
    get_string('description'),
];
$table->data = [];

foreach ($activities as $activity) {
    $url = new moodle_url('/mod/aigradedassign/view.php', ['id' => $activity->coursemodule]);
    $name = html_writer::link($url, format_string($activity->name));
    $description = format_module_intro('aigradedassign', $activity, $activity->coursemodule);
    $table->data[] = [$name, $description];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
