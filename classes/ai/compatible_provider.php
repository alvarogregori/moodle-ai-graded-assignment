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
 * Generic OpenAI Chat Completions compatible provider.
 *
 * @package mod_aigradedassign
 */
final class compatible_provider extends remote_provider {
    /** @var string API key. */
    private string $apikey;
    /** @var string API base URL. */
    private string $baseurl;

    public function __construct() {
        $this->providername = 'compatible';
        $this->apikey = $this->require_apikey('compatible_apikey', 'OpenAI-compatible');
        $this->baseurl = rtrim($this->setting('compatible_baseurl', ''), '/');
        $this->model = $this->setting('compatible_model', '');
        if ($this->baseurl === '' || $this->model === '') {
            throw new \moodle_exception('incompleteproviderconfig', 'aigradedassign', '', 'OpenAI-compatible');
        }
    }

    protected function request(array $messages): string {
        $data = $this->post_json($this->baseurl . '/chat/completions', [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $this->temperature(),
            'max_tokens' => $this->max_tokens(),
            'stream' => false,
        ], ['Authorization: Bearer ' . $this->apikey]);
        $content = $data['choices'][0]['message']['content'] ?? '';
        if (!is_string($content) || trim($content) === '') {
            throw new \moodle_exception('emptyairesponse', 'aigradedassign');
        }
        return $content;
    }
}
