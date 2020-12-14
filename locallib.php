<?php

/**
 * Extracts the import zip contents.
 *
 * @param string $zipfilepath Zip file path
 * @param string $basefilename
 * @return array [0] => \stdClass, [1] => string
 * @throws moodle_exception
 */


require_once 'classes/BlobStorage.php';

/**
 * Make prefix webgl blob file name.
 * @param stdClass $webgl
 * @return string
 */
function cloudstoragewebglcontentprefix(stdClass $webgl){
    $hostname = gethostname();
    return "$hostname-course-$webgl->course"."-module-id-$webgl->id";
}

/**
 * Extracts the imported zip contents.
 * Push to Azure BLOB storage.
 * @param stdClass $webgl
 * @param string $zipfilepath
 * @return array List of imported files.
 * @throws moodle_exception
 */
function import_extract_upload_contents(stdClass $webgl, string $zipfilepath) : array {

    $importtempdir = make_request_directory('webglcontentimport' . microtime(false));

    $zip = new \zip_packer();
    $filelist = $zip->extract_to_pathname($zipfilepath, $importtempdir);
    $dirname = array_key_first($filelist);
    if (!is_dir($importtempdir . DIRECTORY_SEPARATOR . $dirname)) {
        // Missing required file.
        throw new \moodle_exception('invalidcontent', 'mod_webgl');
    }

    $indexfile = $dirname . 'index.html';

    if (!in_array($indexfile, $filelist)) {
        // Missing required file.
        throw new \moodle_exception('errorimport', 'mod_webgl');
    }
    $blobClient = getConnection($webgl->account_name, $webgl->account_key);

    foreach ($filelist as $filename => $value):
        $cfile = $importtempdir . DIRECTORY_SEPARATOR . $filename;
        if (!is_dir($cfile)) {
            $replacewith = cloudstoragewebglcontentprefix($webgl);
            $filename = str_replace_first($filename, '/', $replacewith);
            $contetnttype = mime_content_type($cfile);
            $content = fopen($cfile, "r");
            uploadBlob($blobClient, $filename, $content, $contetnttype, $webgl->container_name);
            if (is_resource($content)) {
                fclose($content);
            }
        }
    endforeach;
    return listBlobs($blobClient, $webgl);
}

/**
 * Extracts the imported zip contents.
 * Push to Azure BLOB storage.
 * @param stdClass $webgl
 * @param string $content
 * @return void
 */
function import_zip_contents(stdClass $webgl, string $content) : void {
    $blobClient = getConnection($webgl->account_name, $webgl->account_key);
    $prefix = cloudstoragewebglcontentprefix($webgl);
    $filename =  $prefix.DIRECTORY_SEPARATOR.$webgl->webgl_file;
    $contetnttype = "application/octet-stream";

    uploadBlob($blobClient, $filename, $content, $contetnttype, $webgl->container_name);
}

/**
 * @param stdClass $webgl
 * @return void
 */
function download_container_blobs(stdClass $webgl): void
{
    $blobClient = getConnection($webgl->account_name, $webgl->account_key);
//    $zipper   = get_file_packer('application/zip');
//    $temppath = make_request_directory() .DIRECTORY_SEPARATOR. $webgl->webgl_file;
    $files = downloadBlobs($blobClient, $webgl);
//    if ($zipper->archive_to_pathname($files, $temppath)) {
//        send_temp_file($temppath, $webgl->webgl_file);
//    } else {
//        print_error('cannotdownloaddir', 'repository');
//    }
}

/**
 * Delete azure blob container content.
 * @param stdClass $webgl
 */
function delete_container_blobs(stdClass $webgl){
    $blobClient = getConnection($webgl->account_name, $webgl->account_key);
    deleteBlobs($blobClient, $webgl);
}

/**
 * @param $haystack
 * @param $needle
 * @param $replace
 * @return string|string[]
 */
function str_replace_first($haystack, $needle, $replace)
{
    $pos = strpos($haystack, $needle);
    if ($pos !== false) {
        return substr_replace($haystack, $replace, 0, $pos );
    }
}

function  activity_navigation($PAGE) {
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
    $sectionurl = new moodle_url('/course/view.php',['id'=>$course->id,'section'=>$section->section]);

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
        $prevtotalurl = '<a href="'.$linkurlprev.'" id="prev-activity-link" class="btn btn-link btn-action text-truncate" title="'.$linknameprev.'">'.$linknameprev.'</a>';
    }


    // Check if we have a next mod to show.
    if ($position < ($nummods - 1)) {
        $nextmod = $mods[$modids[$position + 1]];
        $linkurlnext = new \moodle_url($nextmod->url, array('forceview' => 1));
        $linknamenext = $nextmod->get_formatted_name();
        if (!$nextmod->visible) {
            $linknamenext .= ' ' . get_string('hiddenwithbrackets');
        }
        $nexttotalurl = '<a href="'.$linkurlnext.'" id="next-activity-link" class="btn btn-link btn-action text-truncate" title="'.$linknamenext.'"> '.$linknamenext.'</a>';
    }
    $sectioninfourl = '<a href="'.$sectionurl.'"   id="activity-link" class="btn btn-link btn-action text-truncate" title="'.$section->name.'">' .$section->name.'</a>';

    return '<div class="course-footer-nav">
        <hr class="hr">
        <div class="row">
            <div class="col-sm-12 col-md">
                <div class="pull-left">'.$prevtotalurl.'</div>
            </div>
            <div class="col-sm-12 col-md-2">
                <div class="mdl-align" >'.$sectioninfourl.'</div>
            </div>
            <div class="col-sm-12 col-md">
                <div class="pull-right">'.$nexttotalurl.'</div>
            </div>
        </div>
    </div>';
}



