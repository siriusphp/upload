[![Source Code](https://img.shields.io/badge/source-siriusphp/upload-blue.svg?style=flat-square)](https://github.com/siriusphp/upload)
[![Latest Version](https://img.shields.io/packagist/v/siriusphp/upload.svg?style=flat-square)](https://github.com/siriusphp/upload/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](https://github.com/siriusphp/upload/blob/master/LICENSE)
[![Build Status](https://img.shields.io/travis/siriusphp/upload/master.svg?style=flat-square)](https://travis-ci.org/siriusphp/upload)
[![PHP 7 ready](https:////php7ready.timesplinter.ch/siriusphp/upload/master/badge.svg)](https://travis-ci.org/siriusphp/upload)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/siriusphp/upload.svg?style=flat-square)](https://scrutinizer-ci.com/g/siriusphp/upload/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/siriusphp/upload.svg?style=flat-square)](https://scrutinizer-ci.com/g/siriusphp/upload)
[![Total Downloads](https://img.shields.io/packagist/dt/siriusphp/upload.svg?style=flat-square)](https://packagist.org/packages/siriusphp/upload)

# Sirius Upload

This is a framework agnostic upload handler library that is flexible and easy to use.

## Features

1. Validates files against usual rules: extension, file size, image size (width, height, ratio). It uses [Sirius Validation](https://github.com/siriusphp/validation) for this purpose.
2. Moves valid uploads into containers. Containers are usually local folders but you can implement your own or use other filesystem abstractions like [Gaufrette](https://github.com/KnpLabs/Gaufrette) or [Flysystem](https://github.com/FrenkyNet/Flysystem).
3. Works with PSR7 `UploadedFileInterface` objects and with Symfony's `UploadedFile`s (see [integrations](integrations.md)).

Used by [Bolt CMS](https://bolt.cm/)

## How it works

1. Uploaded file is validated against the rules. By default the library will check if the upload is valid (ie: no errors during upload)
2. The name of the uploaded file is sanitized (keep only letters, numbers and underscore). You may implement your own sanitization function if you want.
3. If overwrite is not allowed, and a file with the same name already exists in the container, the library will prepend the timestamp to the filename.
4. Moves the uploaded file to the container. It also create a lock file (filename + '.lock') so that we know the upload is not confirmed. See [file locking](file_locking.md)
5. If something wrong happens in your app and you want to get rid of the uploaded file you can `clear()` the uploaded file which will remove the file and its `.lock` file. Only files that have a corresponding `.lock` file attached can be cleared
6. If everything is in order you can `confirm` the upload. This will remove the `.lock` file attached to the upload file.

## Important notes

##### 1. The library makes no assumptions about the "web availability" of the uploaded file.

Most of the times once you have a valid upload the new file will be reachable on the internet. You may upload your files to `/var/www/public/images/users/` and have the files accessible at `//cdn.domain.com/users/`. It's up to you to make your app work with the result of the upload.

##### 2. You can handle multiple uploads at once if they have the same name

If you upload multiple files with the same name (eg: `<input type="file" name="pictures[]">`) but you have to keep in mind that the `process()` and `getMessages()` methods will return arrays

```php
$result = $uploadHandler->process($_FILES['pictures']);
// will return a collection of files which implements \Iterator interface
$messages = $result->getMessages();
// may return if the second file is not valid
array(
	'1' => 'File type not accepted'
);
```

In this case the library normalizes the `$_FILES` array as PHP messes up the upload array.
It is up to you to decide what you want to do when some files fail to upload (eg: keep the valid files and discard the failed image or display error messages for the invalid images)
