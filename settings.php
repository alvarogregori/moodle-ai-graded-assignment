<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'mod_aigradedassign/providerheading',
        get_string('providerconfig', 'aigradedassign'),
        get_string('providerconfig_desc', 'aigradedassign')
    ));
}
