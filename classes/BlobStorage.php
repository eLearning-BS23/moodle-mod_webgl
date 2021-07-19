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
require_once($CFG->dirroot . '/mod/webgl/vendor/autoload.php');

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use MicrosoftAzure\Storage\Blob\Models\SetBlobPropertiesOptions;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;

/**
 * @param string $AccountName
 * @param string $AccountKey
 * @return BlobRestProxy
 */
function getConnection(string $AccountName, string $AccountKey)
{
    $connectionString = "DefaultEndpointsProtocol=https;AccountName=$AccountName;AccountKey=$AccountKey;EndpointSuffix=core.windows.net";
    return BlobRestProxy::createBlobService($connectionString);
}

/**
 * Create a new Blob
 * @param BlobRestProxy $blobClient
 * @param $blob_name
 * @param $content
 * @param string $contetnttype
 * @param string $container
 */
function uploadBlob(BlobRestProxy $blobClient, $blob_name, $content, string $contetnttype, string $container)
{
    try {
        $blobClient->createBlockBlob($container, $blob_name, $content);
        $opts = new SetBlobPropertiesOptions();
        $opts->setContentType($contetnttype);
        $blobClient->setBlobProperties($container, $blob_name, $opts);
    } catch (ServiceException $e) {
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code . ": " . $error_message . PHP_EOL;
    }
}

/**
 * Download blob content.
 *
 * @param BlobRestProxy $blobClient
 * @param stdClass $webgl
 * @return void
 * @throws coding_exception
 * @throws moodle_exception
 */
function downloadBlobs(BlobRestProxy $blobClient, stdClass $webgl)
{
    $zipper = get_file_packer('application/zip');
    $temppath = make_request_directory() . DIRECTORY_SEPARATOR . $webgl->webgl_file;
    try {
        // List blobs.
        $listBlobsOptions = new ListBlobsOptions();
        $prefix = cloudstoragewebglcontentprefix($webgl);
        $listBlobsOptions->setPrefix($prefix);
        // Setting max result to 1 is just to demonstrate the continuation token.
        // It is not the recommended value in a product environment.
        $listBlobsOptions->setMaxResults(1);

        do {
            $blob_list = $blobClient->listBlobs($webgl->container_name, $listBlobsOptions);
            foreach ($blob_list->getBlobs() as $blob) {
                $filename = str_replace_first($blob->getName(), '/', "");
                $stream = downloadBlobStreamContent($blobClient, $webgl->container_name, $blob->getName());
                $string_archive[$filename] = [stream_get_contents($stream)];
                if ($zipper->archive_to_pathname($string_archive, $temppath)) {
                    echo 'OKay' . PHP_EOL;
                } else {
                    print_error('cannotdownloaddir', 'repository');
                }
            }
            $listBlobsOptions->setContinuationToken($blob_list->getContinuationToken());
        } while ($blob_list->getContinuationToken());
        send_temp_file($temppath, $webgl->webgl_file);
    } catch (ServiceException $e) {
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code . ": " . $error_message . PHP_EOL;
    }
}

/**
 * List Blobs of a container
 * @param BlobRestProxy $blobClient
 * @param stdClass $webgl
 * @return array
 */
function listBlobs(BlobRestProxy $blobClient, stdClass $webgl)
{
    $contentlist = array();
    try {
        // List blobs.
        $listBlobsOptions = new ListBlobsOptions();
        $prefix = cloudstoragewebglcontentprefix($webgl);
        $listBlobsOptions->setPrefix($prefix);
        // Setting max result to 1 is just to demonstrate the continuation token.
        // It is not the recommended value in a product environment.
        $listBlobsOptions->setMaxResults(1);

        do {
            $blob_list = $blobClient->listBlobs($webgl->container_name, $listBlobsOptions);
            foreach ($blob_list->getBlobs() as $blob) {
                $contentlist[$blob->getName()] = $blob->getUrl();
                if (strpos($blob->getName(), 'index.html') !== false) {
                    $contentlist[BS_WEBGL_INDEX] = $blob->getName();
                }
            }

            $listBlobsOptions->setContinuationToken($blob_list->getContinuationToken());
        } while ($blob_list->getContinuationToken());

    } catch (ServiceException $e) {
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code . ": " . $error_message . PHP_EOL;
    }
    return $contentlist;
}

/**
 * Delete a Blob
 * @param BlobRestProxy $blobClient
 * @param stdClass $webgl
 * @return void
 */
function deleteBlobs(BlobRestProxy $blobClient, stdClass $webgl)
{
    try {
        // List blobs.
        $listBlobsOptions = new ListBlobsOptions();
        $prefix = cloudstoragewebglcontentprefix($webgl);
        $listBlobsOptions->setPrefix($prefix);

        // Setting max result to 1 is just to demonstrate the continuation token.
        // It is not the recommended value in a product environment.
        $listBlobsOptions->setMaxResults(1);

        do {
            $blob_list = $blobClient->listBlobs($webgl->container_name, $listBlobsOptions);
            foreach ($blob_list->getBlobs() as $blob) {
                deleteBlob($blobClient, $webgl->container_name, $blob->getName());
            }

            $listBlobsOptions->setContinuationToken($blob_list->getContinuationToken());
        } while ($blob_list->getContinuationToken());

    } catch (ServiceException $e) {
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code . ": " . $error_message . PHP_EOL;
    }
}

/**
 * Delete a Blob
 * @param BlobRestProxy $blobClient
 * @param string $container
 * @param string $blob_name
 * @return void
 */
function deleteBlob(BlobRestProxy $blobClient, string $container, string $blob_name)
{
    $blobClient->deleteBlob($container, $blob_name);
}

/**
 * @param BlobRestProxy $blobClient
 * @param string $container
 * @param string $blob
 * @return resource|null
 */
function downloadBlobStreamContent(BlobRestProxy $blobClient, string $container, string $blob)
{
    try {
        return $blobClient->getBlob($container, $blob)
            ->getContentStream();
    } catch (ServiceException $e) {
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code . ": " . $error_message . PHP_EOL;
        return null;
    }
}
