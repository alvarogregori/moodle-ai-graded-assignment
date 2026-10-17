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
