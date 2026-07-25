<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_aigradedassign\ai;

/**
 * Creates the evaluation provider selected in an activity.
 *
 * @package    mod_aigradedassign
 * @copyright  2026 Alvaro Gregori
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class provider_factory {
    /**
     * Returns a configured provider.
     *
     * @param string $providername Provider identifier.
     * @return provider
     */
    public static function create(string $providername): provider {
        if ($providername === 'default' || $providername === '') {
            $configured = trim((string) get_config('mod_aigradedassign', 'defaultprovider'));
            $providername = in_array(
                $configured,
                ['mistral', 'openai', 'anthropic', 'compatible', 'mock'],
                true
            ) ? $configured : 'mock';
        }
        return match ($providername) {
            'mistral' => new mistral_provider(),
            'openai' => new openai_provider(),
            'anthropic' => new anthropic_provider(),
            'compatible' => new compatible_provider(),
            'mock' => new mock_provider(),
            default => throw new \moodle_exception('unknownprovider', 'aigradedassign', '', $providername),
        };
    }
}
