<?php
// This file is part of Moodle - http://moodle.org/

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
