<?php

/**
 * AWS webgl module version info
 *
 * @package mod_appstream
 * @copyright  2020 Brain station 23 ltd <>  {@link https://brainstation-23.com/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['modulename'] = 'WebGL';
$string['modulenameplural'] = 'WebGL';
$string['modulename_help'] = 'WebGL is a JavaScript API for rendering interactive 2D and 3D graphics within any compatible web browser without the use of plug-ins. WebGL is fully integrated with other web standards, allowing GPU-accelerated usage of physics and image processing and effects as part of the web page canvas.';
$string['contentheader'] = 'Content';
$string['input:file'] = 'WebGL file';
$string['header:content'] = 'WebGL content';
//TODO: Find these strings in the UI and make sure they are logical
$string['webgl:addinstance'] = 'Add a new WebGL Application.';
$string['webgl:submit'] = 'Submit WebGL Application';
$string['webgl:view'] = 'View webGL';
$string['nowebgls'] = 'No webgl records found in this course.';
$string['appstreamfieldset'] = 'Custom example fieldset';
$string['appstreamname'] = 'WebGL name';
$string['appstreamname_help'] = 'This is the content of the help tooltip associated with the appstreamname field. Markdown syntax is supported.';
$string['webgl'] = 'webgl';
$string['pluginadministration'] = 'webgl administration';
$string['pluginname'] = 'webgl';
$string['ziparchive'] = 'Select a zip file.';
$string['ziparchive_help'] = 'Select a zip file containing index.html, index.liquid, logo, .htaccess and build files and folders.';

$string['content_advcheckbox'] = 'Update WebGL content too';
$string['content_advcheckbox_help'] = 'If enabled,you can also update the WebGL content';


///////////////////////////////////
//BEGIN: Fields in the admin form//
///////////////////////////////////

$string['account_name'] = 'Azure Storage Account Name';
$string['account_name_help'] = 'An Azure storage account contains all of your Azure Storage data objects: blobs, files, queues, tables, and disks. The storage account provides a unique namespace for your Azure Storage data that is accessible from anywhere in the world over HTTP or HTTPS. Data in your Azure storage account is durable and highly available, secure, and massively scalable.';

$string['account_key'] = 'Azure Storage Account Key';
$string['account_key_help'] = 'When you create a storage account, Azure generates two 512-bit storage account access keys. These keys can be used to authorize access to data in your storage account via Shared Key authorization.';

$string['container_name'] = 'Blob storage container';
$string['container_name_help'] = 'Azure Blob Storage helps you create data lakes for your analytics needs, and provides storage to build powerful cloud-native and mobile apps. Optimize costs with tiered storage for your long-term data, and flexibly scale up for high-performance computing and machine learning workloads.';

$string['store_zip_file'] = 'Upload zip file';
$string['store_zip_file_help'] = 'Also upload Uploaded zip file to Azure Blob storage.';

$string['iframe_height'] = 'Content Height';
$string['iframe_height_help'] = 'Height of the Iframe that load WebGL content in (pixels, (r)em, percentages). Default Value is 550px.';

$string['iframe_width'] = 'Content Width';
$string['iframe_width_help'] = 'Width of the Iframe that load WebGL content in (pixels, (r)em, percentages). Default Value is 100%.';
$moduleintro = get_string('moduleintro');

$string['before_description'] = 'Show WebGL content before '.$moduleintro.' section.';
$string['before_description_help'] = 'By default WebGL content will show after '.$moduleintro.' section. Check the checkbox If you want to show content before '.$moduleintro.' section ';

$string['storage'] = 'Storage details';
$string['storage'] = 'Storage details';
/////////////////////////////////
//END: Fields in the admin form//
/////////////////////////////////
