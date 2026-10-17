<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$evaluationid = required_param('evaluationid', PARAM_INT);
$cm = get_coursemodule_from_id('aigradedassign', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$activity = $DB->get_record('aigradedassign', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/aigradedassign:grade', $context);

$sql = "SELECT e.*, s.userid, s.aigradedassignid, s.attemptnumber AS currentattempt,
               s.submissiontext, u.firstname, u.lastname
          FROM {aigradedassign_evaluations} e
          JOIN {aigradedassign_submissions} s ON s.id = e.submissionid
          JOIN {user} u ON u.id = s.userid
         WHERE e.id = :evaluationid
           AND s.aigradedassignid = :activityid";
$evaluation = $DB->get_record_sql($sql, [
    'evaluationid' => $evaluationid,
    'activityid' => $activity->id,
], MUST_EXIST);
if ((int) $evaluation->attemptnumber !== (int) $evaluation->currentattempt) {
    throw new moodle_exception('cannotreviewoldattempt', 'aigradedassign');
}

$url = new moodle_url('/mod/aigradedassign/review.php', [
    'id' => $cm->id,
    'evaluationid' => $evaluation->id,
]);
$PAGE->set_url($url);
$PAGE->set_title(get_string('reviewevaluation', 'aigradedassign'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_cm($cm, $course, $activity);

$form = new \mod_aigradedassign\form\review_form($url, [
    'cmid' => $cm->id,
    'evaluationid' => $evaluation->id,
]);
if ($form->is_cancelled()) {
    redirect(new moodle_url('/mod/aigradedassign/report.php', ['id' => $cm->id]));
}
if ($data = $form->get_data()) {
    $DB->update_record('aigradedassign_evaluations', (object) [
        'id' => $evaluation->id,
        'score' => (float) $data->score,
        'feedbacktext' => trim($data->feedbacktext),
        'reviewstatus' => 'approved',
        'reviewedby' => $USER->id,
        'timereviewed' => time(),
    ]);

    $DB->set_field('aigradedassign_submissions', 'status', 'evaluated', [
        'id' => $evaluation->submissionid,
    ]);
    aigradedassign_update_grades($activity, $evaluation->userid);
    $completion = new completion_info($course);
    $completion->update_state($cm, COMPLETION_COMPLETE, $evaluation->userid);

    redirect(
        new moodle_url('/mod/aigradedassign/report.php', ['id' => $cm->id]),
        get_string('evaluationapproved', 'aigradedassign'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$form->set_data([
    'id' => $cm->id,
    'evaluationid' => $evaluation->id,
    'score' => $evaluation->score,
    'feedbacktext' => $evaluation->feedbacktext,
]);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('reviewevaluationfor', 'aigradedassign', fullname($evaluation)));
echo html_writer::tag('div', s($evaluation->submissiontext), ['class' => 'alert alert-light text-pre-wrap']);
$form->display();
echo $OUTPUT->footer();
