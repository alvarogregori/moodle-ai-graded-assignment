<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

class mod_aigradedassign_mod_form extends moodleform_mod {
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('aigradedassignname', 'aigradedassign'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements(get_string('instructions', 'aigradedassign'));

        $mform->addElement('header', 'rubricheader', get_string('rubric', 'aigradedassign'));
        $mform->addElement('textarea', 'rubrictext', get_string('rubrictextplain', 'aigradedassign'), [
            'rows' => 8,
            'cols' => 80,
        ]);
        $mform->setType('rubrictext', PARAM_TEXT);
        $mform->addRule('rubrictext', null, 'required', null, 'client');

        $mform->addElement('header', 'examplesheader', get_string('examples', 'aigradedassign'));
        $mform->addElement('textarea', 'example1sample', get_string('examplesampleplain', 'aigradedassign'), [
            'rows' => 8,
            'cols' => 80,
        ]);
        $mform->setType('example1sample', PARAM_TEXT);
        $mform->addRule('example1sample', null, 'required', null, 'client');

        $mform->addElement('textarea', 'example1evaluation', get_string('exampleevaluationplain', 'aigradedassign'), [
            'rows' => 8,
            'cols' => 80,
        ]);
        $mform->setType('example1evaluation', PARAM_TEXT);
        $mform->addRule('example1evaluation', null, 'required', null, 'client');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    public function data_preprocessing(&$defaultvalues) {
        global $DB;

        if (empty($defaultvalues['instance'])) {
            return;
        }

        $example = $DB->get_record('aigradedassign_examples', [
            'aigradedassignid' => $defaultvalues['instance'],
            'sortorder' => 1,
        ]);

        if ($example) {
            $defaultvalues['example1sample'] = $example->sampletext;
            $defaultvalues['example1evaluation'] = $example->evaluationtext;
        }
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        foreach (['rubrictext', 'example1sample', 'example1evaluation'] as $field) {
            if (trim($data[$field] ?? '') === '') {
                $errors[$field] = get_string('required');
            }
        }

        return $errors;
    }
}
