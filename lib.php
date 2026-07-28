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
        FEATURE_COMPLETION_HAS_RULES,
        FEATURE_GRADE_HAS_GRADE => true,
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
    $data->provider = aigradedassign_normalise_provider($data->provider ?? 'default');
    $data->requirevalidation = !empty($data->requirevalidation) ? 1 : 0;
    $data->completionevaluated = !empty($data->completionevaluated) ? 1 : 0;
    $data->timecreated = $now;
    $data->timemodified = $now;

    $data->id = $DB->insert_record('aigradedassign', $data);
    aigradedassign_grade_item_update($data);
    return $data->id;
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
    $data->provider = aigradedassign_normalise_provider($data->provider ?? 'default');
    $data->requirevalidation = !empty($data->requirevalidation) ? 1 : 0;
    $data->completionevaluated = !empty($data->completionevaluated) ? 1 : 0;
    $data->timemodified = time();

    $updated = $DB->update_record('aigradedassign', $data);
    aigradedassign_grade_item_update($data);
    aigradedassign_update_grades($data);
    return $updated;
}

/**
 * Validates a provider submitted through the activity form.
 *
 * @param string $provider Provider identifier.
 * @return string A supported provider identifier.
 */
function aigradedassign_normalise_provider(string $provider): string {
    $provider = clean_param($provider, PARAM_ALPHA);
    return in_array($provider, ['default', 'mock', 'mistral', 'openai', 'anthropic', 'compatible'], true)
        ? $provider
        : 'default';
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

    $activity = $DB->get_record('aigradedassign', ['id' => $id], '*', MUST_EXIST);
    aigradedassign_grade_item_delete($activity);

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
 * Creates or updates this activity's gradebook item.
 *
 * @param stdClass $activity Activity instance.
 * @param mixed $grades Grade records accepted by grade_update().
 * @return int Grade update status.
 */
function aigradedassign_grade_item_update($activity, $grades = null): int {
    global $CFG;

    require_once($CFG->libdir . '/gradelib.php');
    $item = [
        'itemname' => $activity->name,
        'gradetype' => GRADE_TYPE_VALUE,
        'grademin' => 0,
        'grademax' => 10,
    ];

    return grade_update(
        'mod/aigradedassign',
        $activity->course,
        'mod',
        'aigradedassign',
        $activity->id,
        0,
        $grades,
        $item
    );
}

/**
 * Synchronises stored AI evaluations with the course gradebook.
 *
 * @param stdClass $activity Activity instance.
 * @param int $userid Optional user id; zero updates all users.
 * @param bool $nullifnone Whether to clear grades when no evaluation exists.
 * @return int Grade update status.
 */
function aigradedassign_update_grades($activity, int $userid = 0, bool $nullifnone = true): int {
    global $DB;

    $params = ['activityid' => $activity->id];
    $userwhere = '';
    if ($userid) {
        $userwhere = ' AND s.userid = :userid';
        $params['userid'] = $userid;
    }
    $sql = "SELECT s.userid, s.timemodified AS datesubmitted,
                   e.score AS rawgrade, e.feedbacktext AS feedback,
                   e.timecreated AS dategraded
              FROM {aigradedassign_submissions} s
              JOIN {aigradedassign_evaluations} e
                ON e.submissionid = s.id
               AND e.attemptnumber = s.attemptnumber
               AND e.reviewstatus = 'approved'
             WHERE s.aigradedassignid = :activityid
                   $userwhere";
    $records = $DB->get_records_sql($sql, $params);
    $grades = [];
    foreach ($records as $record) {
        if ($record->rawgrade === null) {
            continue;
        }
        $record->feedbackformat = FORMAT_PLAIN;
        $grades[$record->userid] = $record;
    }

    if (!$grades && !$nullifnone) {
        return GRADE_UPDATE_OK;
    }
    if ($userid && !$grades && $nullifnone) {
        $grades[$userid] = (object) ['userid' => $userid, 'rawgrade' => null];
    }

    return aigradedassign_grade_item_update($activity, $grades ?: null);
}

/**
 * Deletes this activity's grade item.
 *
 * @param stdClass $activity Activity instance.
 * @return int Grade update status.
 */
function aigradedassign_grade_item_delete($activity): int {
    global $CFG;

    require_once($CFG->libdir . '/gradelib.php');
    return grade_update(
        'mod/aigradedassign',
        $activity->course,
        'mod',
        'aigradedassign',
        $activity->id,
        0,
        null,
        ['deleted' => 1]
    );
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
