<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_aigradedassign\ai;

defined('MOODLE_INTERNAL') || die();

class mistral_client implements provider_interface {
    public function __construct(private readonly string $apikey) {
    }

    public function evaluate(evaluation_request $request): evaluation_response {
        $payload = [
            'model' => $request->model,
            'messages' => $request->messages,
            'temperature' => $request->temperature,
            'max_tokens' => $request->maxtokens,
        ];

        $started = microtime(true);
        $response = $this->post_json('https://api.mistral.ai/v1/chat/completions', $payload, [
            'Authorization: Bearer ' . $this->apikey,
            'Content-Type: application/json',
        ]);
        $latency = (int)round((microtime(true) - $started) * 1000);

        $text = $response['choices'][0]['message']['content'] ?? '';
        if ($text === '') {
            throw new \moodle_exception('emptyairesponse', 'aigradedassign');
        }

        return new evaluation_response(
            $text,
            json_encode($response),
            $response['choices'][0]['finish_reason'] ?? null,
            $response['usage']['prompt_tokens'] ?? null,
            $response['usage']['completion_tokens'] ?? null,
            $latency
        );
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
