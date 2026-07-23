<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_aigradedassign\ai;

defined('MOODLE_INTERNAL') || die();

class evaluation_request {
    public function __construct(
        public readonly string $model,
        public readonly float $temperature,
        public readonly int $maxtokens,
        public readonly array $messages
    ) {
    }
}
