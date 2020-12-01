# Microsoft Azure Storage PHP Client Libraries

This project provides a set of PHP client libraries that make it easy to access Microsoft Azure Storage services (blobs, tables, queues and files). For documentation on how to host PHP applications on Microsoft Azure, please see the [Microsoft Azure PHP Developer Center](http://www.windowsazure.com/en-us/develop/php/).

# Getting Started
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

## Download Source Code

To get the source code from GitHub, type

```
git clone https://github.com/Azure/azure-storage-php.git webgl
cd ./azure-storage-php
```

## Install via Composer

2. Download **[composer.phar](http://getcomposer.org/composer.phar)** in your project root.

3. Open a command prompt and execute this in your project root

```
php composer.phar install
```
