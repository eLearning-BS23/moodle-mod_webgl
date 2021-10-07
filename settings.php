<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

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

    $settings->add(new admin_setting_configtext('webgl/access_key',
        get_string('access_key', 'mod_webgl'),
        get_string('access_key_help', 'mod_webgl'), get_config('s3', 'access_key') ?? '', PARAM_TEXT, 128));

    $settings->add(new admin_setting_configtext('webgl/secret_key',
        get_string('secret_key', 'mod_webgl'),
        get_string('secret_key_help', 'mod_webgl'), get_config('s3', 'secret_key') ?? '', PARAM_TEXT, 50));

    $choices = require('possible_end_points.php');
    $settings->add(new admin_setting_configselect('webgl/endpoint',
        get_string('endpoint', 'mod_webgl'),
        get_string('endpoint_help', 'mod_webgl'), get_config('s3', 'endpoint'), $choices));

    $settings->add(new admin_setting_configtext('webgl/bucket_name',
        get_string('bucket_name', 'mod_webgl'),
        get_string('bucket_name_help', 'mod_webgl'), '', PARAM_TEXT, 90));

//    $settings->add(new admin_setting_configcheckbox('webgl/cloudfront_url',
//        get_string('cloudfront_url', 'mod_webgl'),
//        get_string('cloudfront_url_help', 'mod_webgl'), 1));

    $storage_engines = [
        1 => 'Azure BLOB storage',
        2 => 'AWS Simple Cloud Storage (S3)',
        3 => get_string('local_file_system','mod_webgl'),
    ];
    $settings->add(new admin_setting_configselect('webgl/storage_engine',
        get_string('storage_engine', 'mod_webgl'),
        get_string('storage_engine_help', 'mod_webgl'), '3', $storage_engines));

    $settings->add(new admin_setting_configcheckbox('webgl/store_zip_file',
        get_string('store_zip_file', 'mod_webgl'),
        get_string('store_zip_file_help', 'mod_webgl'), 1));

    $settings->add(new admin_setting_configtext('webgl/iframe_height',
        get_string('iframe_height', 'mod_webgl'),
        get_string('iframe_height_help', 'mod_webgl'), '600px', PARAM_TEXT, 10));

    $settings->add(new admin_setting_configtext('webgl/iframe_width',
        get_string('iframe_width', 'mod_webgl'),
        get_string('iframe_width_help', 'mod_webgl'), '100%', PARAM_TEXT, 10));

}
