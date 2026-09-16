<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Restore structure for mod_aigradedassign.
 *
 * @package    mod_aigradedassign
 * @copyright  2026 Alvaro Gregori
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Restores activity, submission, and evaluation records.
 */
class restore_aigradedassign_activity_structure_step extends restore_activity_structure_step {
    /**
     * Defines the XML paths handled by this step.
     *
     * @return restore_path_element[]
     */
    protected function define_structure(): array {
        $paths = [new restore_path_element('aigradedassign', '/activity/aigradedassign')];
        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element(
                'aigradedassign_submission',
                '/activity/aigradedassign/submissions/submission'
            );
            $paths[] = new restore_path_element(
                'aigradedassign_evaluation',
                '/activity/aigradedassign/submissions/submission/evaluations/evaluation'
            );
        }
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restores the activity instance.
     *
     * @param array|stdClass $data Restored record data.
     */
    protected function process_aigradedassign($data): void {
        global $DB;

        $data = (object) $data;
        $data->course = $this->get_courseid();
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newid = $DB->insert_record('aigradedassign', $data);
        $this->apply_activity_instance($newid);
    }

    /**
     * Restores one student submission.
     *
     * @param array|stdClass $data Restored record data.
     */
    protected function process_aigradedassign_submission($data): void {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->aigradedassignid = $this->get_new_parentid('aigradedassign');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        if (!empty($data->timeevaluated)) {
            $data->timeevaluated = $this->apply_date_offset($data->timeevaluated);
        }

        $newid = $DB->insert_record('aigradedassign_submissions', $data);
        $this->set_mapping('aigradedassign_submission', $oldid, $newid);
    }

    /**
     * Restores one AI evaluation or tutor review.
     *
     * @param array|stdClass $data Restored record data.
     */
    protected function process_aigradedassign_evaluation($data): void {
        global $DB;

        $data = (object) $data;
        $data->submissionid = $this->get_new_parentid('aigradedassign_submission');
        if (!empty($data->reviewedby)) {
            $data->reviewedby = $this->get_mappingid('user', $data->reviewedby, null);
        }
        if (!empty($data->timereviewed)) {
            $data->timereviewed = $this->apply_date_offset($data->timereviewed);
        }
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $DB->insert_record('aigradedassign_evaluations', $data);
    }

    /**
     * Restores files used by the standard activity introduction.
     */
    protected function after_execute(): void {
        $this->add_related_files('mod_aigradedassign', 'intro', null);
    }
}
