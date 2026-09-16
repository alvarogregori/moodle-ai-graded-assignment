<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Privacy API implementation for mod_aigradedassign.
 *
 * @package    mod_aigradedassign
 * @category   privacy
 * @copyright  2026 Alvaro Gregori
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_aigradedassign\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Provides metadata, export, and deletion support for stored assignment data.
 */
final class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {

    /**
     * Describes personal data stored or sent externally by the plugin.
     *
     * @param collection $collection Metadata collection.
     * @return collection Updated collection.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('aigradedassign_submissions', [
            'aigradedassignid' => 'privacy:metadata:submissions:aigradedassignid',
            'userid' => 'privacy:metadata:submissions:userid',
            'submissiontext' => 'privacy:metadata:submissions:submissiontext',
            'status' => 'privacy:metadata:submissions:status',
            'attemptnumber' => 'privacy:metadata:submissions:attemptnumber',
            'timecreated' => 'privacy:metadata:submissions:timecreated',
            'timemodified' => 'privacy:metadata:submissions:timemodified',
            'timeevaluated' => 'privacy:metadata:submissions:timeevaluated',
        ], 'privacy:metadata:aigradedassign_submissions');

        $collection->add_database_table('aigradedassign_evaluations', [
            'submissionid' => 'privacy:metadata:evaluations:submissionid',
            'attemptnumber' => 'privacy:metadata:evaluations:attemptnumber',
            'provider' => 'privacy:metadata:evaluations:provider',
            'model' => 'privacy:metadata:evaluations:model',
            'score' => 'privacy:metadata:evaluations:score',
            'feedbacktext' => 'privacy:metadata:aigradedassign_evaluations:feedbacktext',
            'reviewstatus' => 'privacy:metadata:evaluations:reviewstatus',
            'reviewedby' => 'privacy:metadata:evaluations:reviewedby',
            'timereviewed' => 'privacy:metadata:evaluations:timereviewed',
            'timecreated' => 'privacy:metadata:evaluations:timecreated',
        ], 'privacy:metadata:aigradedassign_evaluations');

        $collection->add_subsystem_link('core_grades', [], 'privacy:metadata:core_grades');
        $collection->add_external_location_link('ai_provider', [
            'submissiontext' => 'privacy:metadata:external:submissiontext',
            'instructions' => 'privacy:metadata:external:instructions',
            'rubric' => 'privacy:metadata:external:rubric',
            'examples' => 'privacy:metadata:external:examples',
        ], 'privacy:metadata:external');

        return $collection;
    }

    /**
     * Finds module contexts containing data for a user.
     *
     * @param int $userid User id.
     * @return contextlist Context list.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm
                    ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {aigradedassign} a ON a.id = cm.instance
             LEFT JOIN {aigradedassign_submissions} s ON s.aigradedassignid = a.id
             LEFT JOIN {aigradedassign_evaluations} e ON e.submissionid = s.id
                 WHERE s.userid = :submissionuserid OR e.reviewedby = :reviewerid";
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'aigradedassign',
            'submissionuserid' => $userid,
            'reviewerid' => $userid,
        ]);
        return $contextlist;
    }

    /**
     * Adds users with submissions or tutor reviews in a module context.
     *
     * @param userlist $userlist User list.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $sql = "SELECT s.userid
                  FROM {course_modules} cm
                  JOIN {aigradedassign_submissions} s ON s.aigradedassignid = cm.instance
                 WHERE cm.id = :cmid
                 UNION
                SELECT e.reviewedby AS userid
                  FROM {course_modules} cm
                  JOIN {aigradedassign_submissions} s ON s.aigradedassignid = cm.instance
                  JOIN {aigradedassign_evaluations} e ON e.submissionid = s.id
                 WHERE cm.id = :reviewcmid AND e.reviewedby IS NOT NULL";
        $userlist->add_from_sql('userid', $sql, [
            'cmid' => $context->instanceid,
            'reviewcmid' => $context->instanceid,
        ]);
    }

    /**
     * Exports submission, evaluation, and review data for a user.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('aigradedassign', $context->instanceid);
            if (!$cm) {
                continue;
            }
            $data = helper::get_context_data($context, $user);
            $submission = $DB->get_record('aigradedassign_submissions', [
                'aigradedassignid' => $cm->instance,
                'userid' => $user->id,
            ]);
            if ($submission) {
                $data->submission = $submission;
                $data->evaluations = array_values($DB->get_records(
                    'aigradedassign_evaluations',
                    ['submissionid' => $submission->id],
                    'attemptnumber ASC'
                ));
            }
            $reviewssql = "SELECT e.*
                             FROM {aigradedassign_evaluations} e
                             JOIN {aigradedassign_submissions} s ON s.id = e.submissionid
                            WHERE s.aigradedassignid = :activityid AND e.reviewedby = :userid";
            $data->tutorreviews = array_values($DB->get_records_sql($reviewssql, [
                'activityid' => $cm->instance,
                'userid' => $user->id,
            ]));
            writer::with_context($context)->export_data([], $data);
        }
    }

    /**
     * Deletes all user data in a module context.
     *
     * @param \context $context Module context.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('aigradedassign', $context->instanceid);
        if ($cm) {
            self::delete_submissions('aigradedassignid = :activityid', ['activityid' => $cm->instance]);
        }
    }

    /**
     * Deletes data for one user in approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('aigradedassign', $context->instanceid);
            if (!$cm) {
                continue;
            }
            self::delete_submissions(
                'aigradedassignid = :activityid AND userid = :userid',
                ['activityid' => $cm->instance, 'userid' => $userid]
            );
            $sql = "UPDATE {aigradedassign_evaluations}
                       SET reviewedby = NULL
                     WHERE reviewedby = :userid
                       AND submissionid IN (
                           SELECT id FROM {aigradedassign_submissions}
                            WHERE aigradedassignid = :activityid
                       )";
            $DB->execute($sql, ['userid' => $userid, 'activityid' => $cm->instance]);
        }
    }

    /**
     * Deletes data for multiple users in one context.
     *
     * @param approved_userlist $userlist Approved users and context.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('aigradedassign', $context->instanceid);
        $userids = $userlist->get_userids();
        if (!$cm || !$userids) {
            return;
        }
        [$usersql, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'privacyuser');
        self::delete_submissions(
            "aigradedassignid = :activityid AND userid {$usersql}",
            ['activityid' => $cm->instance] + $userparams
        );
        $reviewselect = "reviewedby {$usersql} AND submissionid IN (
                            SELECT id FROM {aigradedassign_submissions}
                             WHERE aigradedassignid = :reviewactivityid
                        )";
        $DB->set_field_select(
            'aigradedassign_evaluations',
            'reviewedby',
            null,
            $reviewselect,
            ['reviewactivityid' => $cm->instance] + $userparams
        );
    }

    /**
     * Deletes evaluations before their parent submissions.
     *
     * @param string $select Submission selection SQL.
     * @param array $params Selection parameters.
     */
    private static function delete_submissions(string $select, array $params): void {
        global $DB;

        $submissionids = $DB->get_fieldset_select('aigradedassign_submissions', 'id', $select, $params);
        if ($submissionids) {
            [$insql, $inparams] = $DB->get_in_or_equal($submissionids, SQL_PARAMS_NAMED, 'privacysubmission');
            $DB->delete_records_select('aigradedassign_evaluations', "submissionid {$insql}", $inparams);
        }
        $DB->delete_records_select('aigradedassign_submissions', $select, $params);
    }
}
