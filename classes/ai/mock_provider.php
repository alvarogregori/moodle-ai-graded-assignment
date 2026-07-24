<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_aigradedassign\ai;

/**
 * Deterministic local provider used by the minimum stable release.
 *
 * It never makes a network request and deliberately does not expose private
 * rubric or example content in its response.
 *
 * @package    mod_aigradedassign
 * @copyright  2026 Alvaro Gregori
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class mock_provider implements provider {
    /**
     * Generates deterministic placeholder feedback.
     *
     * @param evaluation_input $input Evaluation context.
     * @return evaluation_result
     */
    public function evaluate(evaluation_input $input): evaluation_result {
        $characters = \core_text::strlen($input->submission);
        $words = count(preg_split('/\s+/u', trim($input->submission), -1, PREG_SPLIT_NO_EMPTY));
        $feedback = get_string('mockfeedback', 'aigradedassign', [
            'characters' => $characters,
            'words' => $words,
        ]);
        return new evaluation_result($feedback, 'mock', 'mock-v1', 10.0);
    }
}
