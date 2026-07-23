<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace mod_aigradedassign\completion;

use core_completion\activity_custom_completion;

/**
 * Custom completion based on a successfully stored evaluation.
 *
 * @package    mod_aigradedassign
 * @copyright  2026 Alvaro Gregori
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {
    /**
     * Gets the state of a custom completion rule.
     *
     * @param string $rule Rule name.
     * @return int Completion state.
     */
    public function get_state(string $rule): int {
        global $DB;

        if ($rule !== 'completionevaluated') {
            return COMPLETION_UNKNOWN;
        }
        $customdata = (array) $this->cm->customdata;
        $enabled = !empty($customdata['customcompletionrules'][$rule]);
        if (!$enabled) {
            return COMPLETION_COMPLETE;
        }
        $complete = $DB->record_exists('aigradedassign_submissions', [
            'aigradedassignid' => $this->cm->instance,
            'userid' => $this->userid,
            'status' => 'evaluated',
        ]);
        return $complete ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Defines every custom rule supported by the plugin.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return ['completionevaluated'];
    }

    /**
     * Returns rules enabled on this activity.
     *
     * @return array
     */
    public function get_available_custom_rules(): array {
        $customdata = (array) $this->cm->customdata;
        return !empty($customdata['customcompletionrules']['completionevaluated'])
            ? ['completionevaluated']
            : [];
    }

    /**
     * Returns human-readable rule descriptions.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        return [
            'completionevaluated' => get_string('completionevaluated_desc', 'aigradedassign'),
        ];
    }

    /**
     * Returns custom rules in their display order.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return ['completionevaluated'];
    }
}
