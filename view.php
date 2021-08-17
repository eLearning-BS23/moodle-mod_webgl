<?php
// This file is part of the Zoom plugin for Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * AWS webgl module version info
 *
 * @package mod_webgl
 * @copyright  2020 Brain station 23 ltd <>  {@link https://brainstation-23.com/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_course\output\activity_navigation;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/classes/BlobStorage.php');
$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or.
$n = optional_param('n', 0, PARAM_INT); // Webgl instance ID - it should be named as the first character of the module.

if ($id) {
    $cm = get_coursemodule_from_id('webgl', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $webgl = $DB->get_record('webgl', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $webgl = $DB->get_record('webgl', array('id' => $n), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $webgl->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('webgl', $webgl->id, $course->id, false, MUST_EXIST);
} else {
    throw new Exception('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

$event = \mod_webgl\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $webgl);
$event->trigger();

// Print the page header.

$PAGE->set_url('/mod/webgl/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($webgl->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_cacheable(false);
$PAGE->set_pagelayout('embedded');
$context = context_course::instance($course->id);
?>
    <style>
        body {
            display: block;
            margin: 0 !important;
        }

        .webgl-iframe-content-loader {
            background: #fff;
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            width: 100%;
            height: 100%;
        }

        .iframe {
            position: relative;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
        }

        .course-footer-nav {
            position: relative;
            left: 0;
            bottom: 10%;
            right: 0;
            width: 100%;
        }

        hr {
            margin-top: 2rem;
            margin-bottom: 2rem;
            border: 0;
            border-top: 0 !important;
        }

        #activity-link {
            position: relative;
            padding: 14px 20px 14px 20px;
            display: inline-block;
            text-align: center;
            vertical-align: middle;
            border-radius: 5px;
            font-size: .85rem;
            font-weight: 500;
            line-height: 180%;
            text-decoration: none;
            -webkit-transition: all 350ms ease;
            -o-transition: all 350ms ease;
            transition: all 350ms ease;
        }
    </style>
<?php
echo $OUTPUT->header();
$iframe = '
<div class="webgl-iframe-content-loader">
<iframe
width="100%"
height="100%"
frameborder="0"
src="' . $webgl->index_file_url . '" ></iframe>';
echo $iframe;

// Get a list of all the activities in the course.
$modules = get_fast_modinfo($course->id)->get_cms();

// Put the modules into an array in order by the position they are shown in the course.
$mods = [];
$activitylist = [];
foreach ($modules as $module) {
    // Only add activities the user can access, aren't in stealth mode and have a url (eg. mod_label does not).
    if (!$module->uservisible || $module->is_stealth() || empty($module->url)) {
        continue;
    }
    $mods[$module->id] = $module;

    // No need to add the current module to the list for the activity dropdown menu.
    if ($module->id == $cm->id) {
        continue;
    }
    // Module name.
    $modname = $module->get_formatted_name();
    // Display the hidden text if necessary.
    if (!$module->visible) {
        $modname .= ' ' . get_string('hiddenwithbrackets');
    }
    // Module URL.
    $linkurl = new moodle_url($module->url, array('forceview' => 1));
    // Add module URL (as key) and name (as value) to the activity list array.
    $activitylist[$linkurl->out(false)] = $modname;
}

$nummods = count($mods);

// If there is only one mod then do nothing.
if ($nummods == 1) {
    return '';
}

// Get an array of just the course module ids used to get the cmid value based on their position in the course.
$modids = array_keys($mods);

// Get the position in the array of the course module we are viewing.
$position = array_search($cm->id, $modids);

$prevmod = null;
$nextmod = null;

// Check if we have a previous mod to show.
if ($position > 0) {
    $prevmod = $mods[$modids[$position - 1]];
}

// Check if we have a next mod to show.
if ($position < ($nummods - 1)) {
    $nextmod = $mods[$modids[$position + 1]];
}

$activitynav = new activity_navigation($prevmod, $nextmod, $activitylist);
$renderer = $PAGE->get_renderer('core', 'course');
?>
    <div onclick="history.back()" style="position: absolute;
        left: 0;width:100%;
        height:30px;bottom: 0;
        background: #fff;
        padding: 12px;color: #424242;
        text-decoration: none;
        font-size: 15px;
        border-radius: 4px;">
        <?php echo $renderer->render($activitynav); ?>
    </div>
<?php
echo '</div>';
echo $OUTPUT->footer();
