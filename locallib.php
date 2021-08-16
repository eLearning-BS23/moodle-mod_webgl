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
 * Locallib.
 *
 * @package mod_webgl
 * @copyright  2020 Brain station 23 ltd <>  {@link https://brainstation-23.com/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once('classes/BlobStorage.php');
require_once('mod_form.php');

/**
 * Extracts the imported zip contents.
 * Push to Azure BLOB storage.
 * @param stdClass $webgl
 * @param string $zipfilepath
 * @return array List of imported files.
 * @throws moodle_exception
 */
function import_extract_upload_contents(stdClass $webgl, string $zipfilepath): array {

    $importtempdir = make_request_directory('webglcontentimport' . microtime(false));

    $zip = new zip_packer();

    $filelist = $zip->extract_to_pathname($zipfilepath, $importtempdir);

    $dirname = array_key_first($filelist);

    if (!is_dir($importtempdir . DIRECTORY_SEPARATOR . $dirname)) {

        $dirnamearr = explode('/', $dirname);

        $dirname = $dirnamearr[0] . DIRECTORY_SEPARATOR;

    }

    if (!is_dir($importtempdir . DIRECTORY_SEPARATOR . $dirname)) {
        // Missing required file.
        throw new moodle_exception('invalidcontent', 'mod_webgl');
    }

    $indexfile = $dirname . 'index.html';

    if (!in_array($indexfile, $filelist)) {
        // Missing required file.
        throw new moodle_exception('errorimport', 'mod_webgl');
    }

    // Upload to S3.
    if ($webgl->storage_engine == mod_webgl_mod_form::STORAGE_ENGINE_S3) {

        $replacewith = cloudstoragewebglcontentprefix($webgl);

        $bucket = $replacewith;

        $endpoint = webgl_s3_upload($webgl, $bucket, $filelist, $importtempdir, $replacewith);

        return ['index' => "https://$endpoint/" . "$bucket/" . $endpoint . '/' . cloudstoragewebglcontentprefix($webgl) . '/index.html'];
    } else {
        // Upload to Azure Blob storage.
        $blobclient = get_connection($webgl->account_name, $webgl->account_key);

        foreach ($filelist as $filename => $value):

            $cfile = $importtempdir . DIRECTORY_SEPARATOR . $filename;

            if (!is_dir($cfile)) {

                $replacewith = cloudstoragewebglcontentprefix($webgl);

                $filename = str_replace_first($filename, '/', $replacewith);

                $contetnttype = mime_content_type($cfile);

                $content = fopen($cfile, "r");

                upload_blob($blobclient, $filename, $content, $contetnttype, $webgl->container_name);

                if (is_resource($content)) {

                    fclose($content);

                }
            }

        endforeach;

        return list_blobs($blobclient, $webgl);
    }

}

/**
 * Upload to s3.
 * @param stdClass $webgl
 * @param string $bucket
 * @param array $filelist
 * @param string $importtempdir
 * @param string $replacewith
 * @return mixed
 * @throws dml_exception
 */
function webgl_s3_upload(stdClass $webgl, string $bucket, $filelist, $importtempdir, string $replacewith) {
    list($s3, $endpoint) = s3_create_bucket($webgl, $bucket);

    foreach ($filelist as $filename => $value):

        $cfile = $importtempdir . DIRECTORY_SEPARATOR . $filename;

        if (!is_dir($cfile)) {

            $filename = str_replace_first($filename, '/', $replacewith);

            $s3->putObject($s3->inputFile($cfile), $bucket, $endpoint . '/' . $filename, S3::ACL_PUBLIC_READ);

        }

    endforeach;
    return $endpoint;
}

/**
 * Upload zip file.
 *
 * @param stdClass $webgl
 * @param moodleform_mod $mform
 * @param string $elname
 * @param string $res
 * @throws dml_exception
 */
function upload_zip_file($webgl, $mform, $elname, $res) {
    if ($webgl->store_zip_file) {

        if ($webgl->storage_engine == mod_webgl_mod_form::STORAGE_ENGINE_AZURE) {

            $zipcontent = $mform->get_file_content($elname);

            import_zip_contents($webgl, $zipcontent);

        } else {
            list($s3, $endpoint) = get_s3_instance($webgl);

            $prefix = cloudstoragewebglcontentprefix($webgl);

            $bucket = $prefix;

            $filename = $prefix . DIRECTORY_SEPARATOR . $webgl->webgl_file;

            $s3->putObject($s3->inputFile($res), $bucket, $endpoint . '/' . $filename, S3::ACL_PUBLIC_READ, [
                'Content-Type' => "application/octet-stream",
            ]);

        }
    }
}

/**
 * Get s3 instance.
 *
 * @param stdClass $webgl
 * @param bool $exceptionenabled
 * @return array
 * @throws dml_exception
 */
function get_s3_instance(stdClass $webgl, $exceptionenabled = true) {
    $accesskey = empty($webgl->access_key) ? $webgl->access_key : get_config('webgl', 'access_key');

    $secretkey = empty($webgl->secret_key) ? $webgl->secret_key : get_config('webgl', 'secret_key');

    $endpoint = empty($webgl->endpoint) ? $webgl->endpoint : get_config('webgl', 'endpoint');

    $s3 = new S3($accesskey, $secretkey, false, $endpoint);

    $s3->setExceptions($exceptionenabled);

    // Port of curl::__construct().
    if (!empty($CFG->proxyhost)) {

        if (empty($CFG->proxyport)) {

            $proxyhost = $CFG->proxyhost;

        } else {

            $proxyhost = $CFG->proxyhost . ':' . $CFG->proxyport;
        }

        $proxytype = CURLPROXY_HTTP;

        $proxyuser = null;

        $proxypass = null;

        if (!empty($CFG->proxyuser) and !empty($CFG->proxypassword)) {

            $proxyuser = $CFG->proxyuser;

            $proxypass = $CFG->proxypassword;

        }

        if (!empty($CFG->proxytype) && $CFG->proxytype == 'SOCKS5') {

            $proxytype = CURLPROXY_SOCKS5;

        }

        $s3->setProxy($proxyhost, $proxyuser, $proxypass, $proxytype);
    }
    return [$s3, $endpoint];
}

/**
 * Make prefix webgl blob file name.
 *
 * @param stdClass $webgl
 * @return string
 */
function cloudstoragewebglcontentprefix(stdClass $webgl) {
    $hostname = gethostname();

    $bucket = "$hostname-course-$webgl->course" . "-module-id-$webgl->id";

    $bucket = strtolower($bucket);

    $bucket = str_replace('_', '-', $bucket);

    $bucket = str_replace('.', '-', $bucket);

    $bucketlength = strlen($bucket);

    if ($bucketlength < 3) {

        $bucket .= random_string(10);

    } elseif ($bucketlength > 63) {

        $excitedlength = $bucketlength - 63;

        $bucket = substr_replace($bucket, "", 20, $excitedlength);

    }

    return $bucket;
}

/**
 * Create s3 bucket.
 *
 * @param stdClass $webgl
 * @param string $bucket
 * @param string $visibility
 * @param string $location
 * @return array
 * @throws dml_exception
 */
function s3_create_bucket(stdClass $webgl, string $bucket, string $visibility = S3::ACL_PRIVATE, string $location = mod_webgl_mod_form::STORAGE_ENGINE_S3_DEFAULT_LOCATION) {
    list($s3, $endpoint) = get_s3_instance($webgl, false);

    $bucketobjectremoved = make_empty_s3_bucket($s3, $bucket);

    if (!$bucketobjectremoved) {

        $s3->putBucket($bucket, $visibility, $location);

    }

    return [$s3, $endpoint];
}

/**
 * Delete s3 Bucket.
 *
 * @param stdClass $webgl
 * @return S3
 * @throws dml_exception
 */
function delete_s3_bucket(stdClass $webgl) {
    list($s3, $endpoint) = get_s3_instance($webgl, false);

    $bucket = cloudstoragewebglcontentprefix($webgl);

    make_empty_s3_bucket($s3, $bucket);

    return $s3->deleteBucket($bucket);
}

/**
 * Make empty s3 bucket.
 *
 * @param S3 $s3
 * @param string $bucket
 * @return bool
 */
function make_empty_s3_bucket(S3 $s3, string $bucket) {

    $objects = $s3->getBucket($bucket);

    if (is_array($objects)) {
        foreach ($objects as $key => $object):

            $s3->deleteObject($bucket, $key);

        endforeach;

        // Bucket exists
        return true;
    }

    return false;

}

/**
 * Extracts the imported zip contents.
 * Push to Azure BLOB storage.
 * @param stdClass $webgl
 * @param string $content
 * @return void
 */
function import_zip_contents(stdClass $webgl, string $content): void {
    $blobclient = get_connection($webgl->account_name, $webgl->account_key);

    $prefix = cloudstoragewebglcontentprefix($webgl);

    $filename = $prefix . DIRECTORY_SEPARATOR . $webgl->webgl_file;

    $contetnttype = "application/octet-stream";

    upload_blob($blobclient, $filename, $content, $contetnttype, $webgl->container_name);
}

/**
 * Download container blobs.
 *
 * @param stdClass $webgl
 * @return void
 * @throws coding_exception
 * @throws moodle_exception
 */
function download_container_blobs(stdClass $webgl): void {
    $blobclient = get_connection($webgl->account_name, $webgl->account_key);
    download_blobs($blobclient, $webgl);
}

/**
 * Delete azure blob container content.
 * @param stdClass $webgl
 */
function delete_container_blobs(stdClass $webgl) {
    $blobclient = get_connection($webgl->account_name, $webgl->account_key);
    delete_blobs($blobclient, $webgl);
}

/**
 * Index file url.
 *
 * @param stdClass $webgl
 * @param array $blobdatadetails
 * @return stdClass
 */
function index_file_url($webgl, $blobdatadetails) {
    if ($webgl->storage_engine == mod_webgl_mod_form::STORAGE_ENGINE_S3) {
        $webgl->index_file_url = $blobdatadetails['index'];
    } else {
        $webgl->index_file_url = $blobdatadetails[$blobdatadetails[BS_WEBGL_INDEX]];
    }
    return $webgl;
}

/**
 * String replace first.
 *
 * @param string $haystack
 * @param string $needle
 * @param string $replace
 * @return string|string[]
 */
function str_replace_first($haystack, $needle, $replace) {
    $pos = strpos($haystack, $needle);
    if ($pos !== false) {
        return substr_replace($haystack, $replace, 0, $pos);
    }
}

/**
 * Activity navigation.
 *
 * @param $PAGE
 * @return string
 * @throws coding_exception
 * @throws moodle_exception
 */
function activity_navigation($PAGE) {
    global $CFG;
    // First we should check if we want to add navigation.
    $context = $PAGE->context;

    // Get a list of all the activities in the course.
    $course = $PAGE->cm->get_course();
    $modules = get_fast_modinfo($course->id)->get_cms();

    $section = 1;

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
        if ($module->id == $PAGE->cm->id) {

            $curentmodsection = $module->get_section_info();
            $section = $curentmodsection;
            continue;
        }
        // Module name.
        $modname = $module->get_formatted_name();
        // Display the hidden text if necessary.
        if (!$module->visible) {
            $modname .= ' ' . get_string('hiddenwithbrackets');
        }
        // Module URL.
        $linkurlnext = new moodle_url($module->url, array('forceview' => 1));
        // Add module URL (as key) and name (as value) to the activity list array.
        $activitylist[$linkurlnext->out(false)] = $modname;
    }

    $nummods = count($mods);

    // If there is only one mod then do nothing.
    if ($nummods == 1) {
        return '';
    }

    // Get an array of just the course module ids used to get the cmid value based on their position in the course.
    $modids = array_keys($mods);

    // Get the position in the array of the course module we are viewing.
    $position = array_search($PAGE->cm->id, $modids);
    $sectionurl = new moodle_url('/course/view.php', ['id' => $course->id, 'section' => $section->section]);

    $prevmod = null;
    $nextmod = null;
    $prevtotalurl = null;
    $nexttotalurl = null;

    // Check if we have a previous mod to show.
    if ($position > 0) {
        $prevmod = $mods[$modids[$position - 1]];
        $linkurlprev = new \moodle_url($prevmod->url, array('forceview' => 1));
        $linknameprev = $prevmod->get_formatted_name();
        if (!$prevmod->visible) {
            $linknameprev .= ' ' . get_string('hiddenwithbrackets');
        }
        $prevtotalurl = '<a href="' . $linkurlprev
            . '" id="prev-activity-link" class="btn btn-link btn-action text-truncate" title="'
            . $linknameprev . '">' . $linknameprev . '</a>';
    }

    // Check if we have a next mod to show.
    if ($position < ($nummods - 1)) {
        $nextmod = $mods[$modids[$position + 1]];
        $linkurlnext = new \moodle_url($nextmod->url, array('forceview' => 1));
        $linknamenext = $nextmod->get_formatted_name();
        if (!$nextmod->visible) {
            $linknamenext .= ' ' . get_string('hiddenwithbrackets');
        }
        $nexttotalurl = '<a href="' . $linkurlnext
            . '" id="next-activity-link" class="btn btn-link btn-action text-truncate" title="'
            . $linknamenext . '"> ' . $linknamenext . '</a>';
    }
    $sectioname = $section->name ?? get_string('sectionname', 'format_' . $course->format) . ' ' . $section->section;
    $sectioninfourl = $section->section > 0 ? '<a href="' . $sectionurl
        . '"   id="activity-link" class="btn btn-link btn-action text-truncate" title="'
        . $sectioname . '">' . $sectioname . '</a>' : '';

    return '<div class="course-footer-nav">
            <hr class="hr">
            <div class="row">
                <div class="col-sm-12 col-md">
                    <div class="pull-left">' . $prevtotalurl . '</div>
                </div>
                <div class="col-sm-12 col-md-2">
                    <div class="mdl-align" >' . $sectioninfourl . '</div>
                </div>
                <div class="col-sm-12 col-md">
                    <div class="pull-right">' . $nexttotalurl . '</div>
                </div>
            </div>
        </div>';
}
