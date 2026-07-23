<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configcheckbox(
        'mod_aigradedassign/enablemistral',
        get_string('setting:enablemistral', 'aigradedassign'),
        get_string('setting:enablemistral_desc', 'aigradedassign'),
        1
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'mod_aigradedassign/mistralapikey',
        get_string('setting:mistralapikey', 'aigradedassign'),
        get_string('setting:apikey_desc', 'aigradedassign'),
        ''
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_aigradedassign/enableopenai',
        get_string('setting:enableopenai', 'aigradedassign'),
        get_string('setting:enableopenai_desc', 'aigradedassign'),
        1
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'mod_aigradedassign/openaiapikey',
        get_string('setting:openaiapikey', 'aigradedassign'),
        get_string('setting:apikey_desc', 'aigradedassign'),
        ''
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_aigradedassign/enableanthropic',
        get_string('setting:enableanthropic', 'aigradedassign'),
        get_string('setting:enableanthropic_desc', 'aigradedassign'),
        1
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'mod_aigradedassign/anthropicapikey',
        get_string('setting:anthropicapikey', 'aigradedassign'),
        get_string('setting:apikey_desc', 'aigradedassign'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'mod_aigradedassign/defaultmaxchars',
        get_string('setting:defaultmaxchars', 'aigradedassign'),
        get_string('setting:defaultmaxchars_desc', 'aigradedassign'),
        60000,
        PARAM_INT
    ));
}
