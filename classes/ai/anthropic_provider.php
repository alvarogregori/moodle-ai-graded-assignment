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
 * Anthropic Messages API provider.
 *
 * @package mod_aigradedassign
 */
final class anthropic_provider extends remote_provider {
    /** @var string API key. */
    private string $apikey;
    /** @var string API base URL. */
    private string $baseurl;

    public function __construct() {
        $this->providername = 'anthropic';
        $this->apikey = $this->require_apikey('anthropic_apikey', 'Anthropic');
        $this->baseurl = rtrim($this->setting('anthropic_baseurl', 'https://api.anthropic.com/v1'), '/');
        $this->model = $this->setting('anthropic_model', 'claude-sonnet-4-5');
    }

    protected function request(array $messages): string {
        $system = '';
        $chat = [];
        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $system .= ($system === '' ? '' : "\n\n") . $message['content'];
            } else {
                $chat[] = ['role' => $message['role'], 'content' => $message['content']];
            }
        }
        $data = $this->post_json($this->baseurl . '/messages', [
            'model' => $this->model,
            'system' => $system,
            'messages' => $chat,
            'temperature' => $this->temperature(1.0),
            'max_tokens' => $this->max_tokens(),
        ], [
            'x-api-key: ' . $this->apikey,
            'anthropic-version: 2023-06-01',
        ]);
        $text = '';
        foreach (($data['content'] ?? []) as $content) {
            if (($content['type'] ?? '') === 'text') {
                $text .= (string) ($content['text'] ?? '');
            }
        }
        if (trim($text) === '') {
            throw new \moodle_exception('emptyairesponse', 'aigradedassign');
        }
        return $text;
    }
}
