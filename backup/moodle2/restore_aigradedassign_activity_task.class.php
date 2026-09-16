<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Restore task for mod_aigradedassign.
 *
 * @package    mod_aigradedassign
 * @copyright  2026 Alvaro Gregori
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/aigradedassign/backup/moodle2/restore_aigradedassign_stepslib.php');

/**
 * Provides all steps required to restore one AI Graded Assignment.
 */
class restore_aigradedassign_activity_task extends restore_activity_task {
    /**
     * No module-specific settings are required.
     */
    protected function define_my_settings(): void {
    }

    /**
     * Adds the structure step.
     */
    protected function define_my_steps(): void {
        $this->add_step(new restore_aigradedassign_activity_structure_step(
            'aigradedassign_structure',
            'aigradedassign.xml'
        ));
    }

    /**
     * No restore log rules are required.
     *
     * @return array
     */
    public static function define_decode_log_rules(): array {
        return [];
    }

    /**
     * Defines content-link decoding rules.
     *
     * @return restore_decode_rule[]
     */
    public static function define_decode_rules(): array {
        return [
            new restore_decode_rule('AIGRADEDASSIGNVIEWBYID', '/mod/aigradedassign/view.php?id=$1', 'course_module'),
            new restore_decode_rule('AIGRADEDASSIGNINDEX', '/mod/aigradedassign/index.php?id=$1', 'course'),
        ];
    }

    /**
     * No restore log rules are required.
     *
     * @return restore_log_rule[]
     */
    public static function define_restore_log_rules(): array {
        return [];
    }
}
