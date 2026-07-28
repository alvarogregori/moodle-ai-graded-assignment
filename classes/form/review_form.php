<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_aigradedassign\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Tutor validation form for an AI evaluation.
 *
 * @package mod_aigradedassign
 */
final class review_form extends \moodleform {
    /**
     * Defines the editable grade and assessment fields.
     */
    public function definition(): void {
        $mform = $this->_form;
        $mform->addElement('text', 'score', get_string('grading', 'aigradedassign'));
        $mform->setType('score', PARAM_FLOAT);
        $mform->addRule('score', null, 'required', null, 'client');

        $mform->addElement('textarea', 'feedbacktext', get_string('assessment', 'aigradedassign'), [
            'rows' => 16,
            'cols' => 90,
        ]);
        $mform->setType('feedbacktext', PARAM_RAW);
        $mform->addRule('feedbacktext', null, 'required', null, 'client');

        $mform->addElement('hidden', 'id', $this->_customdata['cmid']);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'evaluationid', $this->_customdata['evaluationid']);
        $mform->setType('evaluationid', PARAM_INT);
        $this->add_action_buttons(true, get_string('approveevaluation', 'aigradedassign'));
    }

    /**
     * Validates the grade range and assessment.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (!is_numeric($data['score'] ?? null)
                || (float) $data['score'] < 0
                || (float) $data['score'] > 10) {
            $errors['score'] = get_string('errorscorerange', 'aigradedassign');
        }
        if (trim((string) ($data['feedbacktext'] ?? '')) === '') {
            $errors['feedbacktext'] = get_string('required');
        }
        return $errors;
    }
}
