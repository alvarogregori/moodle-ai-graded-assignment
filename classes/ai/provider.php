<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_aigradedassign\ai;

/**
 * Contract implemented by evaluation providers.
 *
 * Future Mistral, OpenAI, and Anthropic adapters must implement only this
 * contract; Moodle pages do not need provider-specific code.
 *
 * @package    mod_aigradedassign
 * @copyright  2026 Alvaro Gregori
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface provider {
    /**
     * Evaluates one plain-text submission.
     *
     * @param evaluation_input $input Provider-neutral prompt context.
     * @return evaluation_result
     */
    public function evaluate(evaluation_input $input): evaluation_result;
}
