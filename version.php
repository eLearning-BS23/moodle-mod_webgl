<?php

/**
 * WebGL plugin version info
 *
 * @package mod_webgl
 * @copyright  2020 Brain station 23 ltd <>  {@link https://brainstation-23.com/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2020112501;       // The current version (Date: YYYYMMDDXX)

$plugin->requires  = 2019111806;    // Requires this Moodle version

$plugin->component = 'mod_webgl'; // Full name of the plugin (used for diagnostics)

$plugin->dependencies = [
    'repository_s3' => 2019111800
];

$plugin->release = 'v-1.0.0';

$plugin->maturity = MATURITY_STABLE;
