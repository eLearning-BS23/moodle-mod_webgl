# Moodle WebGL Activity plugin

Moodle Webgl is an activity module plugin, which provides the capability to include a webgl 
in a course as an activity. Administrator can upload the webgl game in the following ways: 

 - Azure Blob storage WebGL content Upload
 - AWS S3 WebGL content Upload
 - Moodle Local File System API
 
We are constantly improving the plugin, so stay tuned for upcoming versions.
### Features
- Easy to use.
- Full-page webgl content load.
- Support Azure BLOB storage webGL content upload.
- Support Amazon Simple Storage Service (S3) webGL content upload.
- Support webgl zip file upload too.
- Option to download content for further use.
- Support moodle local file system api for storing and serving webgl file





## Dependency: [S3 repository](https://docs.moodle.org/311/en/Amazon_S3_repository) 

## Minimum Requirements

* Moodle 3.8 or higher
* See [composer.json](composer.json) for dependencies
* Required extension for PHP:
  * php_fileinfo.dll
  * php_mbstring.dll
  * php_openssl.dll
  * php_xsl.dll

* Recommended extension for PHP:
  * php_curl.dll

## Installation

### Install by downloading the ZIP file
1. Download zip file from [GitHub Repository](https://github.com/eLearning-BS23/moodle-mod_webgl)
2. Unzip the zip file in `/path/to/moodle/mod/webgl` folder.
3. In your Moodle site (as admin), Visit http://yoursite.com/admin to finish the installation.

### Install using git clone
1. Go to Moodle Project root/mod directory
2. clone code by using following commands
```
$ git clone https://github.com/eLearning-BS23/webgl.git webgl
$ cd webgl
```
3. Open a command prompt and execute following commands (Optional)
```
$ php composer.phar install
```
4. In your Moodle site (as admin), Visit http://yoursite.com/admin to finish the installation.

For More Details, please see [Moodle's Docs page](https://docs.moodle.org/38/en/Installing_plugins) about installing plugin. 

## Download Source Code

To get the source code from GitHub, type

```
$ git clone https://github.com/eLearning-BS23/webgl.git
```

## WebGL Settings
![All Settings](https://user-images.githubusercontent.com/72008371/136256041-297e322a-b0d5-45a5-850f-9bd1c33c48bd.png)
- ### Azure Blob storage settings
![Azure Settings](https://user-images.githubusercontent.com/72008371/136256885-cb2560cf-e1a0-4347-bf69-9cc3b7e1d2fe.png)

- ### AWS S3 settings
![AWS Settings](https://user-images.githubusercontent.com/72008371/136256884-4f6db5ad-a1aa-4f06-8f4c-ed736e3fb784.png)

- ### Other Settings 
![Other Settings](https://user-images.githubusercontent.com/72008371/136256878-5ce2a760-57e4-45f7-bf04-6e3eaf28e7ee.png)
Storage Engine: Webgl provide 3 kind of storage. Moodle default file system, Azure BLOB storage, AWS S3. Pick suitable one.


## Adding WebGL to a course

An WebGL instance can be added to a course in the same way as any other activity:
1. Turn editing on
2. Click 'Add an activity or resource'
3. Select "WebGL"

![activity-selection](https://user-images.githubusercontent.com/72008371/136257457-995b8049-17d4-4a38-84da-afbf43444b6f.png)


## WebGL Add Form
![webgl-from](https://user-images.githubusercontent.com/72008371/136257712-909a3e0e-558f-483c-b746-b08ec8635dfc.png) 

## WebGL Edit Form
![edit-webgl](https://user-images.githubusercontent.com/72008371/136257704-8d815309-1768-49ca-8fcf-553f9b80b328.png)

## WebGL Game View
![webgl-view](https://user-images.githubusercontent.com/72008371/136257848-e61e4fd1-726e-4d5e-96f6-2b02827b78ba.png)

## Author
- [Brain Station 23 Ltd.](https://brainstation-23.com)


## Issue or Feature Request
We try our best to deliver bug-free plugins, but we can not test the plugin for every Moodle version. If you find any bug please report it on 
[GitHub Issue Tracker](https://github.com/eLearning-BS23/moodle-mod_webgl/issues).  Please provide a detailed bug description, including the plugin and Moodle version and, if applicable, a screenshot.

You may also file a request for enhancement on GitHub. 
If we consider the request generally useful, we might implement it in a future version.

## License
This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If not, see [GNU License](http://www.gnu.org/licenses/).