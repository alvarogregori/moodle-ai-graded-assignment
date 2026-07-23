<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Library callbacks for mod_aigradedassign.
 *
 * @package    mod_aigradedassign
 * @copyright  2026 Alvaro Gregori
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Declares the Moodle features supported by this activity.
 *
 * @param string $feature Feature constant.
 * @return bool|null
 */
function aigradedassign_supports($feature) {
    return match ($feature) {
        FEATURE_MOD_INTRO,
        FEATURE_SHOW_DESCRIPTION,
        FEATURE_COMPLETION_HAS_RULES => true,
        FEATURE_GRADE_HAS_GRADE,
        FEATURE_GRADE_OUTCOMES,
        FEATURE_COMPLETION_TRACKS_VIEWS,
        FEATURE_GROUPS,
        FEATURE_GROUPINGS => false,
        default => null,
    };
}

/**
 * Creates an activity instance.
 *
 * @param stdClass $data Form data.
 * @param mod_aigradedassign_mod_form|null $mform Form instance.
 * @return int New instance id.
 */
function aigradedassign_add_instance($data, $mform = null): int {
    global $DB;

    $now = time();
    $data->provider = 'mock';
    $data->completionevaluated = !empty($data->completionevaluated) ? 1 : 0;
    $data->timecreated = $now;
    $data->timemodified = $now;

    return $DB->insert_record('aigradedassign', $data);
}

/**
 * Updates an activity instance.
 *
 * @param stdClass $data Form data.
 * @param mod_aigradedassign_mod_form|null $mform Form instance.
 * @return bool
 */
function aigradedassign_update_instance($data, $mform = null): bool {
    global $DB;

    $data->id = $data->instance;
    $data->provider = 'mock';
    $data->completionevaluated = !empty($data->completionevaluated) ? 1 : 0;
    $data->timemodified = time();

    return $DB->update_record('aigradedassign', $data);
}

/**
 * Deletes an activity instance and its dependent records.
 *
 * @param int $id Instance id.
 * @return bool
 */
function aigradedassign_delete_instance($id): bool {
    global $DB;

    if (!$DB->record_exists('aigradedassign', ['id' => $id])) {
        return false;
    }

    $submissionids = $DB->get_fieldset_select(
        'aigradedassign_submissions',
        'id',
        'aigradedassignid = :activityid',
        ['activityid' => $id]
    );
    if ($submissionids) {
        [$insql, $params] = $DB->get_in_or_equal($submissionids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('aigradedassign_evaluations', "submissionid $insql", $params);
    }
    $DB->delete_records('aigradedassign_submissions', ['aigradedassignid' => $id]);
    $DB->delete_records('aigradedassign', ['id' => $id]);

    return true;
}

/**
 * Supplies safe cached information used while rendering a course page.
 *
 * Keeping this callback small and side-effect free is important because Moodle
 * calls it while rebuilding the course cache.
 *
 * @param stdClass $coursemodule Course-module record.
 * @return cached_cm_info|false
 */
function aigradedassign_get_coursemodule_info($coursemodule) {
    global $DB;

    $activity = $DB->get_record(
        'aigradedassign',
        ['id' => $coursemodule->instance],
        'id,name,intro,introformat,completionevaluated'
    );
    if (!$activity) {
        return false;
    }

    $info = new cached_cm_info();
    $info->name = $activity->name;
    if ($coursemodule->showdescription) {
        $info->content = format_module_intro('aigradedassign', $activity, $coursemodule->id, false);
    }
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $info->customdata['customcompletionrules']['completionevaluated'] =
            (int) $activity->completionevaluated;
    }

    return $info;
}
