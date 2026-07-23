<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_aigradedassign\ai;

defined('MOODLE_INTERNAL') || die();

interface provider_interface {
    public function evaluate(evaluation_request $request): evaluation_response;
}
