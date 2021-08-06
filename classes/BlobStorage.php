<?php
/**
 * LICENSE: The MIT License (the "License")
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * https://github.com/azure/azure-storage-php/LICENSE
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Samples
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

global $CFG;
require_once ($CFG->dirroot . '/mod/webgl/vendor/autoload.php');

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use MicrosoftAzure\Storage\Blob\Models\SetBlobPropertiesOptions;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;

/**
 * @param string $AccountName
 * @param string $AccountKey
 * @return BlobRestProxy
 */
function get_connection(string $accountname, string $accountkey) {
    $connectionstring =
        "DefaultEndpointsProtocol=https;AccountName=$accountname;AccountKey=$accountkey;EndpointSuffix=core.windows.net";
    return BlobRestProxy::createBlobService($connectionstring);
}

/**
 * Create a new Blob
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
                    print_error('cannotdownloaddir', 'repository');
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