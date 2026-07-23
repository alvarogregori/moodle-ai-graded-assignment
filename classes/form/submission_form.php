<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace mod_aigradedassign\form;

/**
 * Plain-text student submission form.
 *
 * @package    mod_aigradedassign
 * @copyright  2026 Alvaro Gregori
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submission_form extends \moodleform {
    /**
     * Defines form fields.
     */
    public function definition(): void {
        $mform = $this->_form;
        $mform->addElement('textarea', 'submissiontext', get_string('submissiontext', 'aigradedassign'), [
            'rows' => 16,
            'cols' => 90,
        ]);
        $mform->setType('submissiontext', PARAM_RAW);
        $mform->addRule('submissiontext', null, 'required', null, 'client');
        $mform->addElement('hidden', 'id', $this->_customdata['cmid']);
        $mform->setType('id', PARAM_INT);
        $this->add_action_buttons(false, get_string('submitwork', 'aigradedassign'));
    }

    /**
     * Validates non-empty plain text.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $text = trim((string) ($data['submissiontext'] ?? ''));
        if ($text === '') {
            $errors['submissiontext'] = get_string('required');
        } else if (\core_text::strlen($text) > 50000) {
            $errors['submissiontext'] = get_string('errormaxchars', 'aigradedassign', 50000);
        }
        return $errors;
    }
}
