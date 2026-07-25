<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'mod_aigradedassign/providerheading',
        get_string('providerconfig', 'aigradedassign'),
        get_string('providerconfig_desc', 'aigradedassign')
    ));
    $settings->add(new admin_setting_configselect(
        'mod_aigradedassign/defaultprovider',
        get_string('defaultprovider', 'aigradedassign'),
        get_string('defaultprovider_desc', 'aigradedassign'),
        'mistral',
        [
            'mistral' => get_string('provider:mistral', 'aigradedassign'),
            'openai' => get_string('provider:openai', 'aigradedassign'),
            'anthropic' => get_string('provider:anthropic', 'aigradedassign'),
            'compatible' => get_string('provider:compatible', 'aigradedassign'),
            'mock' => get_string('provider:mock', 'aigradedassign'),
        ]
    ));

    $providers = [
        'mistral' => ['https://api.mistral.ai/v1', 'mistral-small-latest'],
        'openai' => ['https://api.openai.com/v1', 'gpt-5-mini'],
        'anthropic' => ['https://api.anthropic.com/v1', 'claude-sonnet-4-5'],
        'compatible' => ['', ''],
    ];
    foreach ($providers as $provider => [$baseurl, $model]) {
        $settings->add(new admin_setting_heading(
            'mod_aigradedassign/' . $provider . 'heading',
            get_string('provider:' . $provider, 'aigradedassign'),
            get_string('providersettings_desc', 'aigradedassign')
        ));
        $settings->add(new admin_setting_configpasswordunmask(
            'mod_aigradedassign/' . $provider . '_apikey',
            get_string('apikey', 'aigradedassign'),
            get_string('apikey_desc', 'aigradedassign'),
            ''
        ));
        $settings->add(new admin_setting_configtext(
            'mod_aigradedassign/' . $provider . '_model',
            get_string('model', 'aigradedassign'),
            get_string('model_desc', 'aigradedassign'),
            $model,
            PARAM_TEXT
        ));
        $settings->add(new admin_setting_configtext(
            'mod_aigradedassign/' . $provider . '_baseurl',
            get_string('baseurl', 'aigradedassign'),
            get_string('baseurl_desc', 'aigradedassign'),
            $baseurl,
            PARAM_URL
        ));
    }

    $settings->add(new admin_setting_heading(
        'mod_aigradedassign/generationheading',
        get_string('generationsettings', 'aigradedassign'),
        ''
    ));
    $settings->add(new admin_setting_configtext(
        'mod_aigradedassign/maxtokens',
        get_string('maxtokens', 'aigradedassign'),
        get_string('maxtokens_desc', 'aigradedassign'),
        1200,
        PARAM_INT
    ));
    $settings->add(new admin_setting_configtext(
        'mod_aigradedassign/temperature',
        get_string('temperature', 'aigradedassign'),
        get_string('temperature_desc', 'aigradedassign'),
        '0.2',
        PARAM_FLOAT
    ));
}
