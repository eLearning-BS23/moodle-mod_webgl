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

function extract_import_contents(string $zipfilepath) : array {

    $importtempdir = make_request_directory('webglcontentimport' . microtime(false));

    $zip = new \zip_packer();
    $filelist = $zip->extract_to_pathname($zipfilepath, $importtempdir);
    $dirname = array_key_first($filelist);
    if (!is_dir($importtempdir . DIRECTORY_SEPARATOR . $dirname)) {
        // Missing required file.
        throw new \moodle_exception('invalidcontent', 'mod_webgl');
    }

    $indexfile = $dirname.'index.html';

    if (!in_array($indexfile,$filelist)) {
        // Missing required file.
        throw new \moodle_exception('errorimport', 'mod_webgl');
    }

    foreach ($filelist as $filename => $value):
        $cfile = $importtempdir . DIRECTORY_SEPARATOR . $filename;
        if (!is_dir($cfile)){
            $content = file_get_contents($cfile);
            uploadBlobSample( $filename, $content);
        }
    endforeach;
    die('At last');
}
