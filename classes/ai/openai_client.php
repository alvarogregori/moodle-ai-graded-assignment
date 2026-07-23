<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_aigradedassign\ai;

defined('MOODLE_INTERNAL') || die();

class openai_client implements provider_interface {
    public function __construct(private readonly string $apikey) {
    }

    public function evaluate(evaluation_request $request): evaluation_response {
        $payload = [
            'model' => $request->model,
            'input' => array_map(static function(array $message): array {
                return [
                    'role' => $message['role'] === 'developer' ? 'developer' : $message['role'],
                    'content' => $message['content'],
                ];
            }, $request->messages),
            'temperature' => $request->temperature,
            'max_output_tokens' => $request->maxtokens,
        ];

        $started = microtime(true);
        $response = $this->post_json('https://api.openai.com/v1/responses', $payload, [
            'Authorization: Bearer ' . $this->apikey,
            'Content-Type: application/json',
        ]);
        $latency = (int)round((microtime(true) - $started) * 1000);

        $text = $response['output_text'] ?? $this->extract_output_text($response);
        if ($text === '') {
            throw new \moodle_exception('emptyairesponse', 'aigradedassign');
        }

        return new evaluation_response(
            $text,
            json_encode($response),
            $response['status'] ?? null,
            $response['usage']['input_tokens'] ?? null,
            $response['usage']['output_tokens'] ?? null,
            $latency
        );
    }

    private function extract_output_text(array $response): string {
        $parts = [];
        foreach ($response['output'] ?? [] as $item) {
            foreach ($item['content'] ?? [] as $content) {
                if (($content['type'] ?? '') === 'output_text' && isset($content['text'])) {
                    $parts[] = $content['text'];
                }
            }
        }
        return trim(implode("\n", $parts));
    }

    private function post_json(string $url, array $payload, array $headers): array {
        $curl = new \curl();
        $raw = $curl->post($url, json_encode($payload), [
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 90,
        ]);
        $info = $curl->get_info();

        if (($info['http_code'] ?? 0) >= 400 || $raw === false) {
            throw new \moodle_exception('providerrequestfailed', 'aigradedassign', '', $raw);
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new \moodle_exception('invalidairesponse', 'aigradedassign');
        }
        return $decoded;
    }
}
