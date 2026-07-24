<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_aigradedassign\ai;

/**
 * Mistral Chat Completions provider.
 *
 * @package mod_aigradedassign
 */
final class mistral_provider extends remote_provider {
    /** @var string API key. */
    private string $apikey;
    /** @var string API base URL. */
    private string $baseurl;

    public function __construct() {
        $this->providername = 'mistral';
        $this->apikey = $this->require_apikey('mistral_apikey', 'Mistral');
        $this->baseurl = rtrim($this->setting('mistral_baseurl', 'https://api.mistral.ai/v1'), '/');
        $this->model = $this->setting('mistral_model', 'mistral-small-latest');
    }

    protected function request(array $messages): string {
        $data = $this->post_json($this->baseurl . '/chat/completions', [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $this->temperature(),
            'max_tokens' => $this->max_tokens(),
            'response_format' => ['type' => 'json_object'],
            'stream' => false,
        ], ['Authorization: Bearer ' . $this->apikey]);
        $content = $data['choices'][0]['message']['content'] ?? '';
        if (!is_string($content) || trim($content) === '') {
            throw new \moodle_exception('emptyairesponse', 'aigradedassign');
        }
        return $content;
    }
}
