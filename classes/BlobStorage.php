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
require_once ($CFG->dirroot. '/mod/webgl/vendor/autoload.php');

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\CreateBlockBlobOptions;
use MicrosoftAzure\Storage\Blob\Models\CreateContainerOptions;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use MicrosoftAzure\Storage\Blob\Models\PublicAccessType;
use MicrosoftAzure\Storage\Blob\Models\DeleteBlobOptions;
use MicrosoftAzure\Storage\Blob\Models\GetBlobOptions;
use MicrosoftAzure\Storage\Blob\Models\ContainerACL;
use MicrosoftAzure\Storage\Blob\Models\SetBlobPropertiesOptions;
use MicrosoftAzure\Storage\Blob\Models\ListPageBlobRangesOptions;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Common\Models\Range;
use MicrosoftAzure\Storage\Common\Models\Logging;
use MicrosoftAzure\Storage\Common\Models\Metrics;
use MicrosoftAzure\Storage\Common\Models\RetentionPolicy;
use MicrosoftAzure\Storage\Common\Models\ServiceProperties;

function getConnection(){
    $connectionString = 'DefaultEndpointsProtocol=https;AccountName=rswebgl;AccountKey=p9t5XgfvRhI5CR82wKu1584854B487fo+BRac1/WwInIU+BODd/it5CvXRLugSpG/yGVNuqbnkTwaRM01xTQpw==;EndpointSuffix=core.windows.net';
    return BlobRestProxy::createBlobService($connectionString);
}

/**
 * @param $blobClient
 * @param $blob_name
 * @param $content
 * @param string $contetnttype
 * @param string $container
 */
function uploadBlob($blobClient, $blob_name, $content, string $contetnttype , string $container)
{
    try {
        $blobClient->createBlockBlob($container, $blob_name, $content);
        $opts = new SetBlobPropertiesOptions();
        $opts->setContentType($contetnttype);
        $blobClient->setBlobProperties($container, $blob_name, $opts);
    } catch (ServiceException $e) {
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message.PHP_EOL;
    }
}

/**
 * @param $blobClient
 * @param string $container
 * @return array
 */
function listBlobs($blobClient, string $container)
{
    $contentlist = array();
    try {
        // List blobs.
        $listBlobsOptions = new ListBlobsOptions();

        // Setting max result to 1 is just to demonstrate the continuation token.
        // It is not the recommended value in a product environment.
        $listBlobsOptions->setMaxResults(1);

        do {
            $blob_list = $blobClient->listBlobs($container, $listBlobsOptions);
            foreach ($blob_list->getBlobs() as $blob) {
                $contentlist[$blob->getName()] = $blob->getUrl();
                if (strpos($blob->getName(), 'index.html') !== false){
                    $contentlist[BS_WEBGL_INDEX] = $blob->getName();
                }
            }

            $listBlobsOptions->setContinuationToken($blob_list->getContinuationToken());
        } while ($blob_list->getContinuationToken());

    } catch (ServiceException $e) {
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message.PHP_EOL;
    }
    return $contentlist;
}

