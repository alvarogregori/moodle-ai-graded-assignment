<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_aigradedassign\task;

use mod_aigradedassign\service\evaluation_service;

defined('MOODLE_INTERNAL') || die();

class evaluate_submission_task extends \core\task\adhoc_task {
    public function get_name(): string {
        return get_string('taskevaluatesubmission', 'aigradedassign');
    }

    public function execute(): void {
        $data = $this->get_custom_data();
        (new evaluation_service())->evaluate_submission((int)$data->submissionid, (int)$data->cmid);
    }
}
