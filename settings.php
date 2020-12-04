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
        get_string('account_name', 'mod_webgl'), get_string('account_name_help', 'mod_webgl'), '', PARAM_TEXT, 50));

    $settings->add(new admin_setting_configtext('webgl/AccountKey',
        get_string('account_key', 'mod_webgl'), get_string('account_key_help', 'mod_webgl'), '', PARAM_TEXT, 100));

    $settings->add(new admin_setting_configtext('webgl/ContainerName',
        get_string('container_name', 'mod_webgl'), get_string('container_name_help', 'mod_webgl'), '', PARAM_TEXT, 90));

}
