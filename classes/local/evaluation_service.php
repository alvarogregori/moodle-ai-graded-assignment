<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_aigradedassign\local;

use mod_aigradedassign\ai\evaluation_input;
use mod_aigradedassign\ai\mock_provider;

/**
 * Application service that evaluates and persists a submission.
 *
 * @package    mod_aigradedassign
 * @copyright  2026 Alvaro Gregori
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class evaluation_service {
    /**
     * Evaluates a submission and stores the result.
     *
     * @param stdClass $activity Activity instance.
     * @param stdClass $submission Submission record.
     * @return stdClass Evaluation record.
     */
    public function evaluate(\stdClass $activity, \stdClass $submission): \stdClass {
        global $DB;

        $provider = new mock_provider();
        $result = $provider->evaluate(new evaluation_input(
            \content_to_text($activity->intro, $activity->introformat),
            $activity->rubrictext,
            $activity->exampletext,
            $activity->examplefeedback,
            $submission->submissiontext,
        ));
        $record = (object) [
            'submissionid' => $submission->id,
            'attemptnumber' => $submission->attemptnumber,
            'provider' => $result->provider,
            'model' => $result->model,
            'score' => $result->score,
            'feedbacktext' => $result->feedback,
            'timecreated' => time(),
        ];
        $record->id = $DB->insert_record('aigradedassign_evaluations', $record);
        return $record;
    }
}
