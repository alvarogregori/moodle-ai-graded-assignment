<?php
// This file is part of Moodle - http://moodle.org/

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
