<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_aigradedassign\completion;

use core_completion\activity_custom_completion;

defined('MOODLE_INTERNAL') || die();

class custom_completion extends activity_custom_completion {
    public function get_state(string $rule): int {
        global $DB;

        if ($rule !== 'completionevaluated') {
            return COMPLETION_UNKNOWN;
        }

        $exists = $DB->record_exists('aigradedassign_submissions', [
            'aigradedassignid' => $this->cm->instance,
            'userid' => $this->userid,
            'status' => 'evaluated',
        ]);

        return $exists ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    public static function get_defined_custom_rules(): array {
        return ['completionevaluated'];
    }

    public function get_available_custom_rules(): array {
        return ['completionevaluated'];
    }

    public function get_custom_rule_descriptions(): array {
        return [
            'completionevaluated' => get_string('completionevaluated_desc', 'aigradedassign'),
        ];
    }
}
