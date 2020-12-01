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



function import_extract_upload_contents(string $zipfilepath) : array {

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
    $blobClient = getConnection();

    foreach ($filelist as $filename => $value):
        $cfile = $importtempdir . DIRECTORY_SEPARATOR . $filename;
        if (!is_dir($cfile)){
            $contetnttype = mime_content_type($cfile);
            $content = fopen($cfile, "r");
            uploadBlob( $blobClient, $filename, $content,$contetnttype, AZURE_BLOB_CONTAINER);
            if(is_resource($content)) {
                fclose($content);
            }
        }
    endforeach;
    return listBlobs($blobClient, AZURE_BLOB_CONTAINER);
}
