<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_aigradedassign\ai;

defined('MOODLE_INTERNAL') || die();

class anthropic_client implements provider_interface {
    public function __construct(private readonly string $apikey) {
    }

    public function evaluate(evaluation_request $request): evaluation_response {
        $system = '';
        $messages = [];
        foreach ($request->messages as $message) {
            if ($message['role'] === 'system' || $message['role'] === 'developer') {
                $system .= "\n\n" . $message['content'];
                continue;
            }
            $messages[] = ['role' => 'user', 'content' => $message['content']];
        }

        $payload = [
            'model' => $request->model,
            'max_tokens' => $request->maxtokens,
            'temperature' => $request->temperature,
            'system' => trim($system),
            'messages' => $messages,
        ];

        $started = microtime(true);
        $response = $this->post_json('https://api.anthropic.com/v1/messages', $payload, [
            'x-api-key: ' . $this->apikey,
            'anthropic-version: 2023-06-01',
            'Content-Type: application/json',
        ]);
        $latency = (int)round((microtime(true) - $started) * 1000);

        $text = '';
        foreach ($response['content'] ?? [] as $part) {
            if (($part['type'] ?? '') === 'text') {
                $text .= $part['text'];
            }
        }
        $text = trim($text);
        if ($text === '') {
            throw new \moodle_exception('emptyairesponse', 'aigradedassign');
        }

        return new evaluation_response(
            $text,
            json_encode($response),
            $response['stop_reason'] ?? null,
            $response['usage']['input_tokens'] ?? null,
            $response['usage']['output_tokens'] ?? null,
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
