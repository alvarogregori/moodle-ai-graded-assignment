<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

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
