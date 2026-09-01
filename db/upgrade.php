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

        $exampletext = new xmldb_field('exampletext', XMLDB_TYPE_TEXT, null, null, false, null, null);
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
            null
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
        } else {
            $oldexamplework = new xmldb_field('examplework', XMLDB_TYPE_TEXT);
            $oldexampleevaluation = new xmldb_field('exampleevaluation', XMLDB_TYPE_TEXT);
            if ($dbman->field_exists($activitytable, $oldexamplework)) {
                $DB->execute("UPDATE {aigradedassign}
                                SET exampletext = examplework
                              WHERE exampletext IS NULL");
            }
            if ($dbman->field_exists($activitytable, $oldexampleevaluation)) {
                $DB->execute("UPDATE {aigradedassign}
                                SET examplefeedback = exampleevaluation
                              WHERE examplefeedback IS NULL");
            }
        }

        $provider = new xmldb_field(
            'provider',
            XMLDB_TYPE_CHAR,
            '30',
            null,
            XMLDB_NOTNULL,
            null,
            'mock'
        );
        if (!$dbman->field_exists($activitytable, $provider)) {
            $dbman->add_field($activitytable, $provider);
        }
        $completionevaluated = new xmldb_field(
            'completionevaluated',
            XMLDB_TYPE_INTEGER,
            '1',
            null,
            XMLDB_NOTNULL,
            null,
            '1'
        );
        if (!$dbman->field_exists($activitytable, $completionevaluated)) {
            $dbman->add_field($activitytable, $completionevaluated);
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
        if (!$dbman->table_exists($evaluationtable)) {
            $evaluationtable->add_field(
                'id',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                XMLDB_SEQUENCE
            );
            $evaluationtable->add_field(
                'submissionid',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                null,
                '0'
            );
            $evaluationtable->add_field(
                'attemptnumber',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                null,
                '1'
            );
            $evaluationtable->add_field(
                'provider',
                XMLDB_TYPE_CHAR,
                '30',
                null,
                XMLDB_NOTNULL,
                null,
                'mock'
            );
            $evaluationtable->add_field(
                'model',
                XMLDB_TYPE_CHAR,
                '100',
                null,
                XMLDB_NOTNULL,
                null,
                'legacy'
            );
            $evaluationtable->add_field('feedbacktext', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
            $evaluationtable->add_field(
                'timecreated',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                null,
                '0'
            );
            $evaluationtable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $evaluationtable->add_key(
                'submission',
                XMLDB_KEY_FOREIGN,
                ['submissionid'],
                'aigradedassign_submissions',
                ['id']
            );
            $evaluationtable->add_index(
                'submissionattempt',
                XMLDB_INDEX_UNIQUE,
                ['submissionid', 'attemptnumber']
            );
            $dbman->create_table($evaluationtable);

            $legacyfeedback = new xmldb_field('feedback', XMLDB_TYPE_TEXT);
            if ($dbman->field_exists($submissiontable, $legacyfeedback)) {
                $submissions = $DB->get_records_select(
                    'aigradedassign_submissions',
                    "feedback IS NOT NULL AND feedback <> ''"
                );
                foreach ($submissions as $submission) {
                    $DB->insert_record('aigradedassign_evaluations', (object) [
                        'submissionid' => $submission->id,
                        'attemptnumber' => max(1, (int) $submission->attemptnumber),
                        'provider' => 'mock',
                        'model' => 'legacy',
                        'feedbacktext' => $submission->feedback,
                        'timecreated' => $submission->timeevaluated ?: $submission->timemodified,
                    ]);
                }
            }
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

    if ($oldversion < 2026072302) {
        $dbman = $DB->get_manager();
        $evaluationtable = new xmldb_table('aigradedassign_evaluations');
        $scorefield = new xmldb_field(
            'score',
            XMLDB_TYPE_NUMBER,
            '10, 2',
            null,
            null,
            null,
            null,
            'model'
        );
        if (!$dbman->field_exists($evaluationtable, $scorefield)) {
            $dbman->add_field($evaluationtable, $scorefield);
        }

        $submissiontable = new xmldb_table('aigradedassign_submissions');
        $legacyscorefield = new xmldb_field('score', XMLDB_TYPE_NUMBER);
        $legacyresponsefield = new xmldb_field('airesponse', XMLDB_TYPE_TEXT);
        if ($dbman->field_exists($submissiontable, $legacyscorefield)) {
            $submissions = $DB->get_records_select(
                'aigradedassign_submissions',
                'score IS NOT NULL',
                [],
                '',
                'id,attemptnumber,score,feedback,airesponse'
            );
            foreach ($submissions as $submission) {
                $evaluation = $DB->get_record('aigradedassign_evaluations', [
                    'submissionid' => $submission->id,
                    'attemptnumber' => max(1, (int) $submission->attemptnumber),
                ]);
                if (!$evaluation) {
                    continue;
                }
                $evaluation->score = $submission->score;

                if ($dbman->field_exists($submissiontable, $legacyresponsefield)
                        && !empty($submission->airesponse)) {
                    $rawresponse = json_decode($submission->airesponse, true);
                    $content = $rawresponse['choices'][0]['message']['content'] ?? '';
                    $details = is_string($content) ? json_decode($content, true) : null;
                    if (is_array($details)) {
                        $parts = [trim((string) ($details['feedback'] ?? $submission->feedback))];
                        if (!empty($details['strengths'])) {
                            $parts[] = get_string('strengths', 'aigradedassign') . ":\n- "
                                . implode("\n- ", array_map('trim', $details['strengths']));
                        }
                        if (!empty($details['improvements'])) {
                            $parts[] = get_string('improvements', 'aigradedassign') . ":\n- "
                                . implode("\n- ", array_map('trim', $details['improvements']));
                        }
                        $evaluation->feedbacktext = implode("\n\n", $parts);
                    }
                }
                $DB->update_record('aigradedassign_evaluations', $evaluation);
            }
        }

        upgrade_mod_savepoint(true, 2026072302, 'aigradedassign');
    }

    if ($oldversion < 2026072304) {
        upgrade_mod_savepoint(true, 2026072304, 'aigradedassign');
    }

    if ($oldversion < 2026072400) {
        upgrade_mod_savepoint(true, 2026072400, 'aigradedassign');
    }

    if ($oldversion < 2026072401) {
        upgrade_mod_savepoint(true, 2026072401, 'aigradedassign');
    }

    if ($oldversion < 2026072500) {
        if (get_config('mod_aigradedassign', 'defaultprovider') === false) {
            set_config('defaultprovider', 'mistral', 'mod_aigradedassign');
        }
        upgrade_mod_savepoint(true, 2026072500, 'aigradedassign');
    }

    if ($oldversion < 2026072800) {
        $dbman = $DB->get_manager();
        $activitytable = new xmldb_table('aigradedassign');
        $requirevalidation = new xmldb_field(
            'requirevalidation',
            XMLDB_TYPE_INTEGER,
            '1',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'provider'
        );
        if (!$dbman->field_exists($activitytable, $requirevalidation)) {
            $dbman->add_field($activitytable, $requirevalidation);
        }

        $evaluationtable = new xmldb_table('aigradedassign_evaluations');
        $reviewstatus = new xmldb_field(
            'reviewstatus',
            XMLDB_TYPE_CHAR,
            '20',
            null,
            XMLDB_NOTNULL,
            null,
            'approved',
            'feedbacktext'
        );
        if (!$dbman->field_exists($evaluationtable, $reviewstatus)) {
            $dbman->add_field($evaluationtable, $reviewstatus);
        }
        $reviewedby = new xmldb_field(
            'reviewedby',
            XMLDB_TYPE_INTEGER,
            '10',
            null,
            null,
            null,
            null,
            'reviewstatus'
        );
        if (!$dbman->field_exists($evaluationtable, $reviewedby)) {
            $dbman->add_field($evaluationtable, $reviewedby);
        }
        $timereviewed = new xmldb_field(
            'timereviewed',
            XMLDB_TYPE_INTEGER,
            '10',
            null,
            null,
            null,
            null,
            'reviewedby'
        );
        if (!$dbman->field_exists($evaluationtable, $timereviewed)) {
            $dbman->add_field($evaluationtable, $timereviewed);
        }

        upgrade_mod_savepoint(true, 2026072800, 'aigradedassign');
    }

    if ($oldversion < 2026090100) {
        $dbman = $DB->get_manager();
        $activitytable = new xmldb_table('aigradedassign');
        $previousfield = 'examplefeedback';
        foreach ([2, 3] as $number) {
            foreach (['exampletext', 'examplefeedback'] as $basename) {
                $fieldname = $basename . $number;
                $field = new xmldb_field(
                    $fieldname,
                    XMLDB_TYPE_TEXT,
                    null,
                    null,
                    null,
                    null,
                    null,
                    $previousfield
                );
                if (!$dbman->field_exists($activitytable, $field)) {
                    $dbman->add_field($activitytable, $field);
                }
                $previousfield = $fieldname;
            }
        }

        upgrade_mod_savepoint(true, 2026090100, 'aigradedassign');
    }
    return true;
}
