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
 * AWS AppStream module version info
 *
 * @package mod_appstream
 * @copyright  2020 Brain station 23 ltd <>  {@link https://brainstation-23.com/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once 'AWSAppStream.php';
$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$n = optional_param('n', 0, PARAM_INT);  // ... appstream instance ID - it should be named as the first character of the module.
$force_app_assign = optional_param('force_app_assign', 0, PARAM_INT);  // ... appstream instance ID - it should be named as the first character of the module.
if ($id) {
    $cm = get_coursemodule_from_id('appstream', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $appstream = $DB->get_record('appstream', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $appstream = $DB->get_record('appstream', array('id' => $n), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $appstream->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('appstream', $appstream->id, $course->id, false, MUST_EXIST);
} else {
    throw new Exception('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

$event = \mod_appstream\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $appstream);
$event->trigger();

// Print the page header.

$PAGE->set_url('/mod/appstream/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($appstream->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_cacheable(false);
$context = context_course::instance($course->id);

echo $OUTPUT->header();
if (has_capability("mod/appstream:addinstance", $context)) {
    $user_report_url = new moodle_url('/mod/appstream/user-reports.php', array('id' => $cm->id));
    ?>
    <p style="text-align: center">
        <a target="_self" class="btn btn-primary" href="<?php echo $user_report_url; ?>">Users Report</a></p>
    <?php
}
if ($appstream->intro) {
    echo $OUTPUT->box(format_module_intro('appstream', $appstream, $cm->id), 'generalbox mod_introbox', 'appstreamintro');
}
echo '<br>';


$user_enroled = is_enrolled($context, $USER->id, '', true);

//When User enroled to the course then go to the ....
if ($user_enroled) {
// Finish the page.
    $local_record = local_appstream_record($USER->id, $appstream->id);

    if (!empty($local_record)) {
        if ($force_app_assign) {
            $response2 = appstream_assign_fleet($appstream, $USER);
            $local_record->is_fleet_assigned = 1;
            update_local_appstream_record($local_record);
            echo '<h4 align="center">' . get_string('account_created_msg', 'mod_appstream') . '</h4>';
        }
        if ($local_record->is_fleet_assigned) {
            // account created and fleet asigned
            $url = 'https://appstream2.' . $appstream->region . '.aws.amazon.com/userpools#/signin';
            echo '<p align="center"><a target="_tab" class="btn btn-outline-danger" href="' . $url . '">' . get_string('clickhere', 'mod_appstream') . '</a></p>';
            $reloadurl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" . "&force_app_assign=1";
            echo '<p align="center"><a target="_self" class="btn btn-primary" href="' . $reloadurl . '">' . get_string('fleet_assign_click_force', 'mod_appstream') . '</a></p>';
        } else {
            // Fleet not assigned.
            try {
                $response2 = appstream_assign_fleet($appstream, $USER);
                $local_record->is_fleet_assigned = 1;
                update_local_appstream_record($local_record);
                echo '<h4 align="center">' . get_string('account_created_msg', 'mod_appstream') . '</h4>';
                $url = 'https://appstream2.' . $appstream->region . '.aws.amazon.com/userpools#/signin';
                echo '<p align="center"><a target="_tab" class="btn btn-outline-danger" href="' . $url . '">' . get_string('clickhere', 'mod_appstream') . '</a></p>';

            } catch (\Aws\Exception\AwsException $exception) {
                $message = $exception->getAwsErrorMessage();
                echo '<div class="alert alert-danger" role="alert" >';
                echo $message ? $message : 'Something went wrong! Please try again later.';
                echo '</div>';
                echo '<p align="center"><a target="_self" class="btn btn-outline-danger" href="javascript:window.location.href=window.location.href">' . get_string('fleet_assign_click', 'mod_appstream') . '</a></p>';

            }
            $response2 = appstream_assign_fleet($appstream, $USER);
        }
    } else {//Neither User not created nor fleet assigned
        $transaction = $DB->start_delegated_transaction();
        try {
            store_to_apstream_local_record($USER->id, $appstream->id, 0);
            $response1 = appstream_create_user($appstream, $USER);
            $transaction->allow_commit();
            echo '<p align="center"><a target="_self" class="btn btn-outline-danger" href="javascript:window.location.href=window.location.href">' . get_string('fleet_assign_click', 'mod_appstream') . '</a></p>';
        } catch (\Aws\Exception\AwsException $exception) {
            if ($exception->getAwsErrorCode() === 'ResourceAlreadyExistsException') {
                $transaction->allow_commit();
                ?>
                <script type="text/javascript">
                    window.location.reload();
                </script>
                <?php
            } else {
                $message = $exception->getAwsErrorMessage();
                echo '<div class="alert alert-danger" role="alert" >';
                echo $message ? $message : 'Something went wrong! Please try again later or contact with course administrator ';
                echo '</div>';
            }

        }
    }
}
//else{
//    echo '<div class="alert alert-danger" role="alert" >';
//    echo get_string('user_not_enrolled_msg', 'mod_appstream');
//    echo '</div>';
//}
echo $OUTPUT->footer();
