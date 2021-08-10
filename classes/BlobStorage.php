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
 * Defines Blob Storage.
 *
 * @package mod_webgl
 * @copyright  2020 Brain station 23 ltd <>  {@link https://brainstation-23.com/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->dirroot . '/mod/webgl/vendor/autoload.php');

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use MicrosoftAzure\Storage\Blob\Models\SetBlobPropertiesOptions;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;

/**
 * Establish connection
 *
 * @param string $accountname
 * @param string $accountkey
 * @return BlobRestProxy
 */
function get_connection(string $accountname, string $accountkey) {
    $connectionstring =
        "DefaultEndpointsProtocol=https;AccountName=$accountname;AccountKey=$accountkey;EndpointSuffix=core.windows.net";
    return BlobRestProxy::createBlobService($connectionstring);
}

/**
 * Create a new Blob
 *
 * @param BlobRestProxy $blobclient
 * @param $blobname
 * @param $content
 * @param string $contetnttype
 * @param string $container
 */
function upload_blob(BlobRestProxy $blobclient, $blobname, $content, string $contetnttype, string $container) {
    try {
        $blobclient->createBlockBlob($container, $blobname, $content);
        $opts = new SetBlobPropertiesOptions();
        $opts->setContentType($contetnttype);
        $blobclient->setBlobProperties($container, $blobname, $opts);
    } catch (ServiceException $e) {
        $code = $e->getCode();
        $errormessage = $e->getMessage();
        echo $code . ": " . $errormessage . PHP_EOL;
    }
}

/**
 * Download blob content.
 *
 * @param BlobRestProxy $blobclient
 * @param stdClass $webgl
 * @return void
 * @throws coding_exception
 * @throws moodle_exception
 */
function download_blobs(BlobRestProxy $blobclient, stdClass $webgl) {
    $zipper = get_file_packer('application/zip');
    $temppath = make_request_directory() . DIRECTORY_SEPARATOR . $webgl
            ->webgl_file;
    try {
        // List blobs.
        $listblobsoptions = new ListBlobsOptions();
        $prefix = cloudstoragewebglcontentprefix($webgl);
        $listblobsoptions->setPrefix($prefix);
        // Setting max result to 1 is just to demonstrate the continuation token.
        // It is not the recommended value in a product environment.
        $listbloboptions->setMaxResults(1);

        do {
            $bloblist = $blobclient->listBlobs($webgl->container_name, $listblobsoptions);
            foreach ($bloblist->getBlobs() as $blob) {
                $filename = str_replace_first($blob->getName(), '/', "");
                $stream = downloadBlobStreamContent($blobclient, $webgl->container_name, $blob->getName());
                $stringarchive[$filename] = [stream_get_contents($stream)];
                if ($zipper->archive_to_pathname($stringarchive, $temppath)) {
                    echo 'OKay' . PHP_EOL;
                } else {
                    throw new moodle_exception('cannotdownloaddir', 'repository');
                }
            }
            $listblobsoptions->setContinuationToken($bloblist
                ->getContinuationToken());
        } while ($bloblist->getContinuationToken());
        send_temp_file($temppath, $webgl->webgl_file);
    } catch (ServiceException $e) {
        $code = $e->getCode();
        $errormessage = $e->getMessage();
        echo $code . ": " . $errormessage . PHP_EOL;
    }
}

/**
 * List Blobs of a container
 * @param BlobRestProxy $blobclient
 * @param stdClass $webgl
 * @return array
 */
function list_blobs(BlobRestProxy $blobclient, stdClass $webgl) {
    $contentlist = array();
    try {
        // List blobs.
        $listblobsoptions = new ListBlobsOptions();
        $prefix = cloudstoragewebglcontentprefix($webgl);
        $listblobsoptions->setPrefix($prefix);
        // Setting max result to 1 is just to demonstrate the continuation token.
        // It is not the recommended value in a product environment.
        $listblobsoptions->setMaxResults(1);

        do {
            $bloblist = $blobclient->listBlobs($webgl->container_name,
                $listblobsoptions);
            foreach ($bloblist->getBlobs() as $blob) {
                $contentlist[$blob->getName()] = $blob->getUrl();
                if (strpos($blob->getName(), 'index.html') !== false) {
                    $contentlist[BS_WEBGL_INDEX] = $blob->getName();
                }
            }

            $listblobsoptions->setContinuationToken($bloblist
                ->getContinuationToken());
        } while ($bloblist->getContinuationToken());

    } catch (ServiceException $e) {
        $code = $e->getCode();
        $errormessage = $e->getMessage();
        echo $code . ": " . $errormessage . PHP_EOL;
    }
    return $contentlist;
}

/**
 * Delete a Blob
 * @param BlobRestProxy $blobclient
 * @param stdClass $webgl
 * @return void
 */
function delete_blobs(BlobRestProxy $blobclient, stdClass $webgl) {
    try {
        // List blobs.
        $listblobsoptions = new ListBlobsOptions();
        $prefix = cloudstoragewebglcontentprefix($webgl);
        $listblobsoptions->setPrefix($prefix);

        // Setting max result to 1 is just to demonstrate the continuation token.
        // It is not the recommended value in a product environment.
        $listblobsoptions->setMaxResults(1);

        do {
            $bloblist = $blobclient->listBlobs($webgl->container_name,
                $listblobsoptions);
            foreach ($bloblist->getBlobs() as $blob) {
                deleteBlob($blobclient, $webgl->container_name, $blob->getName());
            }

            $listblobsoptions->setContinuationToken($bloblist
                ->getContinuationToken());
        } while ($bloblist->getContinuationToken());

    } catch (ServiceException $e) {
        $code = $e->getCode();
        $errormessage = $e->getMessage();
        echo $code . ": " . $errormessage . PHP_EOL;
    }
}

/**
 * Delete a Blob
 * @param BlobRestProxy $blobclient
 * @param string $container
 * @param string $blobname
 * @return void
 */
function delete_blob(BlobRestProxy $blobclient, string $container, string $blobname) {
    $blobclient->deleteBlob($container, $blobname);
}

/**
 * Download blob stream content.
 * @param BlobRestProxy $blobclient
 * @param string $container
 * @param string $blob
 * @return resource|null
 */
function download_blob_stream_content(BlobRestProxy $blobclient, string $container, string $blob) {
    try {
        return $blobclient->getBlob($container, $blob)
            ->getContentStream();
    } catch (ServiceException $e) {
        $code = $e->getCode();
        $errormessage = $e->getMessage();
        echo $code . ": " . $errormessage . PHP_EOL;
        return null;
    }
}
