<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Activity settings form.
 *
 * @package    mod_aigradedassign
 * @copyright  2026 Alvaro Gregori
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Defines the activity settings form.
 */
class mod_aigradedassign_mod_form extends moodleform_mod {
    /**
     * Defines form elements.
     */
    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('aigradedassignname', 'aigradedassign'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements(get_string('instructions', 'aigradedassign'));

        $mform->addElement('header', 'evaluationheader', get_string('evaluationcontext', 'aigradedassign'));
        $mform->addElement('textarea', 'rubrictext', get_string('rubric', 'aigradedassign'), [
            'rows' => 10,
            'cols' => 80,
        ]);
        $mform->setType('rubrictext', PARAM_RAW);
        $mform->addRule('rubrictext', null, 'required', null, 'client');
        $mform->addHelpButton('rubrictext', 'rubric', 'aigradedassign');

        $mform->addElement('textarea', 'exampletext', get_string('exampletext', 'aigradedassign'), [
            'rows' => 8,
            'cols' => 80,
        ]);
        $mform->setType('exampletext', PARAM_RAW);
        $mform->addRule('exampletext', null, 'required', null, 'client');

        $mform->addElement('textarea', 'examplefeedback', get_string('examplefeedback', 'aigradedassign'), [
            'rows' => 8,
            'cols' => 80,
        ]);
        $mform->setType('examplefeedback', PARAM_RAW);
        $mform->addRule('examplefeedback', null, 'required', null, 'client');

        $mform->addElement('select', 'provider', get_string('provider', 'aigradedassign'), [
            'default' => get_string('provider:default', 'aigradedassign'),
            'mock' => get_string('provider:mock', 'aigradedassign'),
            'mistral' => get_string('provider:mistral', 'aigradedassign'),
            'openai' => get_string('provider:openai', 'aigradedassign'),
            'anthropic' => get_string('provider:anthropic', 'aigradedassign'),
            'compatible' => get_string('provider:compatible', 'aigradedassign'),
        ]);
        $mform->setDefault('provider', 'default');
        $mform->addHelpButton('provider', 'provider', 'aigradedassign');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Adds the custom automatic-completion rule.
     *
     * @return array Form element names.
     */
    public function add_completion_rules(): array {
        $name = $this->get_suffixed_name('completionevaluated');
        $this->_form->addElement('checkbox', $name, '', get_string('completionevaluated', 'aigradedassign'));
        $this->_form->setDefault($name, 1);
        return [$name];
    }

    /**
     * Reports whether the custom completion rule is enabled.
     *
     * @param array $data Submitted form data.
     * @return bool
     */
    public function completion_rule_enabled($data): bool {
        return !empty($data[$this->get_suffixed_name('completionevaluated')]);
    }

    /**
     * Applies Moodle's completion-form suffix to a custom field name.
     *
     * Moodle 4.3 and later use a suffix to prevent duplicate completion field
     * ids when multiple forms are present on the same page.
     *
     * @param string $fieldname Base field name.
     * @return string Suffixed field name.
     */
    protected function get_suffixed_name(string $fieldname): string {
        return $fieldname . $this->get_suffix();
    }

    /**
     * Validates private plain-text context.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array Validation errors.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        foreach (['rubrictext', 'exampletext', 'examplefeedback'] as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                $errors[$field] = get_string('required');
            }
        }
        return $errors;
    }
}
