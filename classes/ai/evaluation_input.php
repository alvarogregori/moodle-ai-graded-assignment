<?php
// This file is part of Moodle - http://moodle.org/

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
