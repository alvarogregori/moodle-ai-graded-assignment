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
 * Immutable provider-neutral evaluation input.
 *
 * @package    mod_aigradedassign
 * @copyright  2026 Alvaro Gregori
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class evaluation_input {
    /**
     * Constructor.
     *
     * @param string $instructions Student-visible instructions.
     * @param string $rubric Private rubric.
     * @param array $examples Private evaluated examples.
     * @param string $submission Student submission.
     */
    public function __construct(
        public readonly string $instructions,
        public readonly string $rubric,
        public readonly array $examples,
        public readonly string $submission,
    ) {
    }
}
