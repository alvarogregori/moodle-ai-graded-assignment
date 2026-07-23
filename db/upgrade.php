<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

function xmldb_aigradedassign_upgrade($oldversion) {
    global $DB;

    if ($oldversion < 2026060501) {
        $DB->set_field_select(
            'aigradedassign',
            'allowedfiletypes',
            '.txt,.doc,.docx',
            "allowedfiletypes = :oldtypes",
            ['oldtypes' => '.txt,.pdf,.doc,.docx']
        );

        upgrade_mod_savepoint(true, 2026060501, 'aigradedassign');
    }

    return true;
}
