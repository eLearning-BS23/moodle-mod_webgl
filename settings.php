<?php

/**
 * WebGL plugin settings.
 *
 * @package mod_webgl
 * @copyright  2020 Brain station 23 ltd <>  {@link https://brainstation-23.com/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_configtext('webgl/AccountName',
        get_string('account_name', 'mod_webgl'),
        get_string('account_name_help', 'mod_webgl'), '', PARAM_TEXT, 50));

    $settings->add(new admin_setting_configtext('webgl/AccountKey',
        get_string('account_key', 'mod_webgl'),
        get_string('account_key_help', 'mod_webgl'), '', PARAM_TEXT, 100));

    $settings->add(new admin_setting_configtext('webgl/ContainerName',
        get_string('container_name', 'mod_webgl'),
        get_string('container_name_help', 'mod_webgl'), '', PARAM_TEXT, 90));

    $settings->add(new admin_setting_configcheckbox('webgl/store_zip_file',
        get_string('store_zip_file', 'mod_webgl'),
        get_string('store_zip_file_help', 'mod_webgl'), 1));


    $settings->add(new admin_setting_configtext('webgl/iframe_height',
        get_string('iframe_height', 'mod_webgl'),
        get_string('iframe_height_help', 'mod_webgl'), '600px', PARAM_TEXT,10));

    $settings->add(new admin_setting_configtext('webgl/iframe_width',
        get_string('iframe_width', 'mod_webgl'),
        get_string('iframe_width_help', 'mod_webgl'), '100%', PARAM_TEXT,10));

}
