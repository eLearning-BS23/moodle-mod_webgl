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
    return "course-$webgl->course"."-module-id-$webgl->id";
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


