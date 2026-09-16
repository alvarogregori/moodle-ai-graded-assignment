<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Backup task for mod_aigradedassign.
 *
 * @package    mod_aigradedassign
 * @copyright  2026 Alvaro Gregori
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/aigradedassign/backup/moodle2/backup_aigradedassign_stepslib.php');

/**
 * Provides all steps required to back up one AI Graded Assignment.
 */
class backup_aigradedassign_activity_task extends backup_activity_task {
    /**
     * No module-specific settings are required.
     */
    protected function define_my_settings(): void {
    }

    /**
     * Adds the structure step.
     */
    protected function define_my_steps(): void {
        $this->add_step(new backup_aigradedassign_activity_structure_step(
            'aigradedassign_structure',
            'aigradedassign.xml'
        ));
    }

    /**
     * Encodes links to this activity in backed-up content.
     *
     * @param string $content Content to transform.
     * @return string
     */
    public static function encode_content_links($content): string {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');
        $content = preg_replace(
            "/({$base}\\/mod\\/aigradedassign\\/index.php\\?id=)([0-9]+)/",
            '$@AIGRADEDASSIGNINDEX*$2@$',
            $content
        );
        return preg_replace(
            "/({$base}\\/mod\\/aigradedassign\\/view.php\\?id=)([0-9]+)/",
            '$@AIGRADEDASSIGNVIEWBYID*$2@$',
            $content
        );
    }
}
