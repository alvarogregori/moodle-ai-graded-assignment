<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_aigradedassign\ai;

/**
 * Immutable result returned by an evaluation provider.
 *
 * @package    mod_aigradedassign
 * @copyright  2026 Alvaro Gregori
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class evaluation_result {
    /**
     * Constructor.
     *
     * @param string $feedback Plain-text feedback.
     * @param string $provider Provider identifier.
     * @param string $model Model identifier.
     */
    public function __construct(
        public readonly string $feedback,
        public readonly string $provider,
        public readonly string $model,
    ) {
    }
}
