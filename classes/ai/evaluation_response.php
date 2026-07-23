<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_aigradedassign\ai;

defined('MOODLE_INTERNAL') || die();

class evaluation_response {
    public function __construct(
        public readonly string $text,
        public readonly ?string $rawresponse = null,
        public readonly ?string $finishreason = null,
        public readonly ?int $inputtokens = null,
        public readonly ?int $outputtokens = null,
        public readonly ?int $latencyms = null
    ) {
    }
}
