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
 * OpenAI Responses API provider.
 *
 * @package mod_aigradedassign
 */
final class openai_provider extends remote_provider {
    /** @var string API key. */
    private string $apikey;
    /** @var string API base URL. */
    private string $baseurl;

    public function __construct() {
        $this->providername = 'openai';
        $this->apikey = $this->require_apikey('openai_apikey', 'OpenAI');
        $this->baseurl = rtrim($this->setting('openai_baseurl', 'https://api.openai.com/v1'), '/');
        $this->model = $this->setting('openai_model', 'gpt-5-mini');
    }

    protected function request(array $messages): string {
        $input = [];
        foreach ($messages as $message) {
            $input[] = [
                'role' => $message['role'],
                'content' => [['type' => 'input_text', 'text' => $message['content']]],
            ];
        }
        $data = $this->post_json($this->baseurl . '/responses', [
            'model' => $this->model,
            'input' => $input,
            'temperature' => $this->temperature(),
            'max_output_tokens' => $this->max_tokens(),
            'text' => ['format' => ['type' => 'json_object']],
        ], ['Authorization: Bearer ' . $this->apikey]);

        if (!empty($data['output_text']) && is_string($data['output_text'])) {
            return $data['output_text'];
        }
        foreach (($data['output'] ?? []) as $output) {
            foreach (($output['content'] ?? []) as $content) {
                if (($content['type'] ?? '') === 'output_text' && !empty($content['text'])) {
                    return (string) $content['text'];
                }
            }
        }
        throw new \moodle_exception('emptyairesponse', 'aigradedassign');
    }
}
