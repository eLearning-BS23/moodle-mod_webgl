<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 3
// of the License.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/gpl-3.0.html>.

/**
 * Library of interface functions and constants for module webgl
 *
 * @package mod_webgl
 * @copyright  2020 Brain station 23 ltd <>  {@link https://brainstation-23.com/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

const BS_WEBGL_INDEX = 'bs_webgl_index';
const AZURE_BLOB_DEFAULT_CONTENT_TYPE = 'text/plain';

require_once 'locallib.php';
require_once $CFG->dirroot.'/repository/s3/S3.php';

/**
 * Returns the information on whether the module supports a feature
 *
 * See {@link plugin_supports()} for more info.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function webgl_supports(string $feature): ?bool
{

    switch($feature) {
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_MOD_INTRO:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_BACKUP_MOODLE2:
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the webgl into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $webgl Submitted data from the form in mod_form.php
 * @param mod_webgl_mod_form|null $mform The form instance itself (if needed)
 * @return int The id of the newly inserted webgl record
 * @throws dml_exception|moodle_exception
 */
function webgl_add_instance(stdClass $webgl, mod_webgl_mod_form $mform = null): int
{
    global $DB;
    $elname = 'importfile';
    $webgl->webgl_file = $mform->get_new_filename('importfile');
    $webgl->timecreated = time();
    $webgl->id = $DB->insert_record('webgl', $webgl);
    $res = $mform->save_temp_file($elname);
    $blobdatadetails = import_extract_upload_contents($webgl, $res);
    $webgl = index_file_url($webgl, $blobdatadetails);
    $DB->update_record('webgl', $webgl);

    upload_zip_file($webgl, $mform, $elname, $res);


    if ($res){
        @unlink($res);
    }
    return $webgl->id;
}


/**
 * Updates an instance of the webgl in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $webgl An object from the form in mod_form.php
 * @param mod_webgl_mod_form|null $mform The form instance itself (if needed)
 * @return boolean Success/Fail
 * @throws dml_exception
 * @throws moodle_exception
 */
function webgl_update_instance(stdClass $webgl, mod_webgl_mod_form $mform = null): bool
{
    global $DB,$USER;
    $elname = 'importfile';

    // You may have to add extra stuff in here.
    $webgl->timemodified = time();
    $webgl->id = $webgl->instance;
    $basefilename = $mform->get_new_filename($elname);
    if ($basefilename){
        $res = $mform->save_temp_file('importfile');
        $blobdatadetails = import_extract_upload_contents($webgl, $res);
        $webgl = index_file_url($webgl, $blobdatadetails);

        $webgl->webgl_file = $basefilename;

        upload_zip_file($webgl, $mform, $elname, $res);


        if ($res){
            @unlink($res);
        }

    }

    return $DB->update_record('webgl', $webgl);
}


/**
 * This standard function will check all instances of this module
 * and make sure there are up-to-date events created for each of them.
 * If courseid = 0, then every webgl event in the site is checked, else
 * only webgl events belonging to the course specified are checked.
 * This is only required if the module is generating calendar events.
 *
 * @param int $courseid Course ID
 * @return bool
 * @throws dml_exception
 */
function webgl_refresh_events($courseid = 0): bool
{
    global $DB;

    if ($courseid == 0) {
        if (!$webgls = $DB->get_records('webgl')) {
            return true;
        }
    } else {
        if (!$webgls = $DB->get_records('webgl', array('course' => $courseid))) {
            return true;
        }
    }

    foreach ($webgls as $webgl) {
        // Create a function such as the one below to deal with updating calendar events.
        // webgl_update_events($webgl);
    }

    return true;
}

/**
 * Removes an instance of the webgl from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 * @throws dml_exception
 */
function webgl_delete_instance($id): bool
{
    global $DB;

    if (! $webgl = $DB->get_record('webgl', array('id' => $id))) {
        return false;
    }

    // Delete any dependent records here.

    $DB->delete_records('webgl', array('id' => $webgl->id));
    if ($webgl->storage_engine == 2){
        delete_s3_bucket($webgl);
    }else{
        delete_container_blobs($webgl);
    }
//    webgl_grade_item_delete($webgl);

    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param stdClass $course The course record
 * @param stdClass $user The user record
 * @param cm_info|stdClass $mod The course module info object or record
 * @param stdClass $webgl The webgl instance record
 * @return stdClass|null
 */
function webgl_user_outline($course, $user, $mod, $webgl): ?stdClass
{

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * It is supposed to echo directly without returning a value.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $webgl the module instance record
 */
function webgl_user_complete($course, $user, $mod, $webgl) {
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in webgl activities and print it out.
 *
 * @param stdClass $course The course record
 * @param bool $viewfullnames Should we display full names
 * @param int $timestart Print activity since this timestamp
 * @return boolean True if anything was printed, otherwise false
 */
function webgl_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link webgl_print_recent_mod_activity()}.
 *
 * Returns void, it adds items into $activities and increases $index.
 *
 * @param array $activities sequentially indexed array of objects with added 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 */
function webgl_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@link webgl_get_recent_mod_activity()}
 *
 * @param stdClass $activity activity record with added 'cmid' property
 * @param int $courseid the id of the course we produce the report for
 * @param bool $detail print detailed report
 * @param array $modnames as returned by {@link get_module_types_names()}
 * @param bool $viewfullnames display users' full names
 */
function webgl_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 *
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * Note that this has been deprecated in favour of scheduled task API.
 *
 * @return boolean
 */
function webgl_cron () {
    return true;
}

/**
 * Returns all other caps used in the module
 *
 * For example, this could be array('moodle/site:accessallgroups') if the
 * module uses that capability.
 *
 * @return array
 */
function webgl_get_extra_capabilities() {
    return array();
}

/* Gradebook API */

/**
 * Is a given scale used by the instance of webgl?
 *
 * This function returns if a scale is being used by one webgl
 * if it has support for grading and scales.
 *
 * @param int $webglid ID of an instance of this module
 * @param int $scaleid ID of the scale
 * @return bool true if the scale is used by the given webgl instance
 */
function webgl_scale_used($webglid, $scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('webgl', array('id' => $webglid, 'grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if scale is being used by any instance of webgl.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param int $scaleid ID of the scale
 * @return boolean true if the scale is used by any webgl instance
 */
function webgl_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('webgl', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Creates or updates grade item for the given webgl instance
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $webgl instance object with extra cmidnumber and modname property
 * @param bool $reset reset grades in the gradebook
 * @return void
 */
function webgl_grade_item_update(stdClass $webgl, $reset=false) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $item = array();
    $item['itemname'] = clean_param($webgl->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;

    if ($webgl->grade > 0) {
        $item['gradetype'] = GRADE_TYPE_VALUE;
        $item['grademax']  = $webgl->grade;
        $item['grademin']  = 0;
    } else if ($webgl->grade < 0) {
        $item['gradetype'] = GRADE_TYPE_SCALE;
        $item['scaleid']   = -$webgl->grade;
    } else {
        $item['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($reset) {
        $item['reset'] = true;
    }

    grade_update('mod/webgl', $webgl->course, 'mod', 'webgl',
        $webgl->id, 0, null, $item);
}

/**
 * Delete grade item for given webgl instance
 *
 * @param stdClass $webgl instance object
 * @return grade_item
 */
function webgl_grade_item_delete($webgl) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/webgl', $webgl->course, 'mod', 'webgl',
        $webgl->id, 0, null, array('deleted' => 1));
}

/**
 * Update webgl grades in the gradebook
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $webgl instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 */
function webgl_update_grades(stdClass $webgl, $userid = 0) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    // Populate array of grade objects indexed by userid.
    $grades = array();

    grade_update('mod/webgl', $webgl->course, 'mod', 'webgl', $webgl->id, 0, $grades);
}

/* File API */

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function webgl_get_file_areas($course, $cm, $context): array
{
    return array();
}

/**
 * File browsing support for webgl file areas
 *
 * @package mod_webgl
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function webgl_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename): ?file_info
{
    return null;
}

/**
 * Serves the files from the webgl file areas
 *
 * @package mod_webgl
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the webgl's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function webgl_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    send_file_not_found();
}

/* Navigation API */

/**
 * Extends the global navigation tree by adding webgl nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the webgl module instance
 * @param stdClass $course current course record
 * @param stdClass $module current webgl instance record
 * @param cm_info $cm course module information
 */
function webgl_extend_navigation(navigation_node $navref, stdClass $course, stdClass $module, cm_info $cm) {
    // TODO Delete this function and its docblock, or implement it.
}

/**
 * Extends the settings navigation with the webgl settings
 *
 * This function is called when the context for the page is a webgl module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav complete settings navigation tree
 * @param navigation_node $webglnode webgl administration node
 */
function webgl_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $webglnode=null) {
    // TODO Delete this function and its docblock, or implement it.
}
