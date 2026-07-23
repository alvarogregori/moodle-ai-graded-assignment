<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade steps for mod_aigradedassign.
 *
 * @param int $oldversion Installed version.
 * @return bool
 */
function xmldb_aigradedassign_upgrade($oldversion): bool {
    global $DB;

    if ($oldversion < 2026072300) {
        $dbman = $DB->get_manager();
        $activitytable = new xmldb_table('aigradedassign');

        $exampletext = new xmldb_field('exampletext', XMLDB_TYPE_TEXT, null, null, false, null, null, 'rubricformat');
        if (!$dbman->field_exists($activitytable, $exampletext)) {
            $dbman->add_field($activitytable, $exampletext);
        }
        $examplefeedback = new xmldb_field(
            'examplefeedback',
            XMLDB_TYPE_TEXT,
            null,
            null,
            false,
            null,
            null,
            'exampletext'
        );
        if (!$dbman->field_exists($activitytable, $examplefeedback)) {
            $dbman->add_field($activitytable, $examplefeedback);
        }

        $examplestable = new xmldb_table('aigradedassign_examples');
        if ($dbman->table_exists($examplestable)) {
            $examples = $DB->get_records('aigradedassign_examples', ['sortorder' => 1]);
            foreach ($examples as $example) {
                $DB->set_field('aigradedassign', 'exampletext', $example->sampletext, [
                    'id' => $example->aigradedassignid,
                ]);
                $DB->set_field('aigradedassign', 'examplefeedback', $example->evaluationtext, [
                    'id' => $example->aigradedassignid,
                ]);
            }
            $dbman->drop_table($examplestable);
        }

        $submissiontable = new xmldb_table('aigradedassign_submissions');
        $oldsubmissiontext = new xmldb_field('extractedtext', XMLDB_TYPE_TEXT);
        $newsubmissiontext = new xmldb_field('submissiontext', XMLDB_TYPE_TEXT);
        if ($dbman->field_exists($submissiontable, $oldsubmissiontext)
                && !$dbman->field_exists($submissiontable, $newsubmissiontext)) {
            $dbman->rename_field($submissiontable, $oldsubmissiontext, 'submissiontext');
        }

        $oldstable = new xmldb_table('aigradedassign_evals');
        $evaluationtable = new xmldb_table('aigradedassign_evaluations');
        if ($dbman->table_exists($oldstable) && !$dbman->table_exists($evaluationtable)) {
            $dbman->rename_table($oldstable, 'aigradedassign_evaluations');
        }
        if ($dbman->table_exists($evaluationtable)) {
            $oldfeedback = new xmldb_field('evaluationtext', XMLDB_TYPE_TEXT);
            $newfeedback = new xmldb_field('feedbacktext', XMLDB_TYPE_TEXT);
            if ($dbman->field_exists($evaluationtable, $oldfeedback)
                    && !$dbman->field_exists($evaluationtable, $newfeedback)) {
                $dbman->rename_field($evaluationtable, $oldfeedback, 'feedbacktext');
            }
            $attempt = new xmldb_field(
                'attemptnumber',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                null,
                '1',
                'submissionid'
            );
            if (!$dbman->field_exists($evaluationtable, $attempt)) {
                $dbman->add_field($evaluationtable, $attempt);
            }
        }

        upgrade_mod_savepoint(true, 2026072300, 'aigradedassign');
    }
    return true;
}
