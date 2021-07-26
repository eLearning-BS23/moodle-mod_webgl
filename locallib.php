<?php


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

    $zip = new zip_packer();
    $filelist = $zip->extract_to_pathname($zipfilepath, $importtempdir);
    $dirname = array_key_first($filelist);

    if (!is_dir($importtempdir . DIRECTORY_SEPARATOR . $dirname)) {
        $dirnamearr = explode('/',$dirname);
        $dirname = $dirnamearr[0].DIRECTORY_SEPARATOR;
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

    if ($webgl->storage_engine == mod_webgl_mod_form::STORAGE_ENGINE_S3){

        $replacewith = cloudstoragewebglcontentprefix($webgl);
        $bucket = $replacewith;
        list($s3, $endpoint) = s3_create_bucket($bucket);

        foreach ($filelist as $filename => $value):
            $cfile = $importtempdir . DIRECTORY_SEPARATOR . $filename;
            if (!is_dir($cfile)) {
                $filename = str_replace_first($filename, '/', $replacewith);
                $s3->putObject(S3::inputFile($cfile),$bucket,$endpoint.'/'.$filename,S3::ACL_PUBLIC_READ);
            }
        endforeach;
        return ['index' => "https://$bucket.$endpoint/".$endpoint.'/'. cloudstoragewebglcontentprefix($webgl).'/index.html'];
    }else{

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
 * @throws coding_exception
 * @throws moodle_exception
 */
function download_container_blobs(stdClass $webgl): void
{
    $blobClient = getConnection($webgl->account_name, $webgl->account_key);
    downloadBlobs($blobClient, $webgl);
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
 * @param string $bucket
 * @param string $visibility
 * @param string $location
 * @return array
 * @throws dml_exception
 */
function s3_create_bucket(string $bucket, string $visibility=S3::ACL_PRIVATE, string $location=mod_webgl_mod_form::STORAGE_ENGINE_S3_DEFAULT_LOCATION)
{
    $bucket = strtolower($bucket);
    $bucket = str_replace('_', '-',$bucket);
    $bucket_length = strlen($bucket);
    if( $bucket_length < 3){
        $bucket .= random_string(10);
    }elseif($bucket_length>63){
        $excited_length = $bucket_length - 63;
        $bucket = substr_replace($bucket,"", rand(15,20), $excited_length);
    }
    list($s3, $endpoint) = get_s3_instance();
    $s3->putBucket($bucket, $visibility, $location);
    return [$s3, $endpoint];
}

/**
 * @throws dml_exception
 */
function delete_s3_bucket(stdClass $webgl) {
    list($s3, $endpoint) = get_s3_instance();
    $bucket = cloudstoragewebglcontentprefix($webgl);
    $objects =  $s3->getBucket($bucket);
    foreach ($objects as $key => $object):
       $s3->deleteObject($bucket,$key);
    endforeach;
    return $s3->deleteBucket($bucket);
}

/**
 * @return array
 * @throws dml_exception
 */
function get_s3_instance(){
    $access_key = get_config('webgl','access_key');
    $secret_key = get_config('webgl','secret_key');
    $endpoint = get_config('webgl','endpoint');
    $s3 = new S3($access_key,$secret_key,false,$endpoint);
    $s3->setExceptions(true);

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


function upload_zip_file($webgl, $mform, $elname, $res)
{
    if ($webgl->store_zip_file) {

        if ($webgl->storage_engine == 1) {
            $zipcontent = $mform->get_file_content($elname);
            import_zip_contents($webgl, $zipcontent);
        } else {
            list($s3, $endpoint) = get_s3_instance();

            $prefix = cloudstoragewebglcontentprefix($webgl);
            $bucket = $prefix;
            $filename = $prefix . DIRECTORY_SEPARATOR . $webgl->webgl_file;
            $s3->putObject(S3::inputFile($res), $bucket, $endpoint . '/' . $filename, S3::ACL_PUBLIC_READ, [
                'Content-Type' => "application/octet-stream"
            ]);
        }
    }
}

/**
 * @param $webgl
 * @param $blobdatadetails
 * @return mixed
 */
function index_file_url($webgl, $blobdatadetails) {
    if ($webgl->storage_engine == mod_webgl_mod_form::STORAGE_ENGINE_S3){
        $webgl->index_file_url = $blobdatadetails['index'];
    }else{
        $webgl->index_file_url = $blobdatadetails[$blobdatadetails[BS_WEBGL_INDEX]];
    }
    return $webgl;
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

/**
 * @param $PAGE
 * @return string
 * @throws coding_exception
 * @throws moodle_exception
 */
function  activity_navigation($PAGE) {
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
    $sectioname = $section->name ?? get_string('sectionname','format_'.$course->format).' '.$section->section;
    $sectioninfourl = $section->section > 0 ? '<a href="'.$sectionurl.'"   id="activity-link" class="btn btn-link btn-action text-truncate" title="'.$sectioname.'">' .$sectioname.'</a>':'';

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



