<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Backup structure for mod_aigradedassign.
 *
 * @package    mod_aigradedassign
 * @copyright  2026 Alvaro Gregori
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the activity, submission, and evaluation data to back up.
 */
class backup_aigradedassign_activity_structure_step extends backup_activity_structure_step {
    /**
     * Builds the nested backup structure.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $activity = new backup_nested_element('aigradedassign', ['id'], [
            'name', 'intro', 'introformat', 'rubrictext',
            'exampletext', 'examplefeedback', 'exampletext2', 'examplefeedback2',
            'exampletext3', 'examplefeedback3', 'provider', 'requirevalidation',
            'completionevaluated', 'timecreated', 'timemodified',
        ]);
        $submissions = new backup_nested_element('submissions');
        $submission = new backup_nested_element('submission', ['id'], [
            'userid', 'submissiontext', 'status', 'attemptnumber',
            'timecreated', 'timemodified', 'timeevaluated',
        ]);
        $evaluations = new backup_nested_element('evaluations');
        $evaluation = new backup_nested_element('evaluation', ['id'], [
            'attemptnumber', 'provider', 'model', 'score', 'feedbacktext',
            'reviewstatus', 'reviewedby', 'timereviewed', 'timecreated',
        ]);

        $activity->add_child($submissions);
        $submissions->add_child($submission);
        $submission->add_child($evaluations);
        $evaluations->add_child($evaluation);

        $activity->set_source_table('aigradedassign', ['id' => backup::VAR_ACTIVITYID]);
        if ($userinfo) {
            $submission->set_source_table(
                'aigradedassign_submissions',
                ['aigradedassignid' => backup::VAR_PARENTID]
            );
            $evaluation->set_source_table(
                'aigradedassign_evaluations',
                ['submissionid' => backup::VAR_PARENTID]
            );
        }

        $submission->annotate_ids('user', 'userid');
        $evaluation->annotate_ids('user', 'reviewedby');
        $activity->annotate_files('mod_aigradedassign', 'intro', null);

        return $this->prepare_activity_structure($activity);
    }
}
