<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_aigradedassign\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class submission_form extends \moodleform {
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('textarea', 'submissiontext', get_string('submissiontext', 'aigradedassign'), [
            'rows' => 10,
            'cols' => 80,
        ]);
        $mform->setType('submissiontext', PARAM_TEXT);
        $mform->addRule('submissiontext', null, 'required', null, 'client');

        $mform->addElement('hidden', 'id', $this->_customdata['cmid']);
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons(true, get_string('submitwork', 'aigradedassign'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (trim($data['submissiontext'] ?? '') === '') {
            $errors['submissiontext'] = get_string('required');
        }

        return $errors;
    }
}
