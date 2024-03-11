# Sirius\Upload

[![Source Code](https://img.shields.io/badge/source-siriusphp/upload-blue.svg?style=flat-square)](https://github.com/siriusphp/upload)
[![Latest Version](https://img.shields.io/packagist/v/siriusphp/upload.svg?style=flat-square)](https://github.com/siriusphp/upload/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](https://github.com/siriusphp/upload/blob/master/LICENSE)
[![Build Status](https://github.com/siriusphp/upload/actions/workflows/ci.yml/badge.svg)](https://github.com/siriusphp/upload/actions/workflows/ci.yml)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/siriusphp/upload.svg?style=flat-square)](https://scrutinizer-ci.com/g/siriusphp/upload/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/siriusphp/upload.svg?style=flat-square)](https://scrutinizer-ci.com/g/siriusphp/upload)
[![Total Downloads](https://img.shields.io/packagist/dt/siriusphp/upload.svg?style=flat-square)](https://packagist.org/packages/siriusphp/upload)

Framework agnostic upload handler library.

## Features

1. Validates files against usual rules: extension, file size, image size (wdith, height, ratio). It uses [Sirius Validation](https://github.com/siriusphp/validation) for this purpose.
2. Moves valid uploaded files into containers. Containers are usually local folders but you can implement your own or use other filesystem abstractions like [Gaufrette](https://github.com/KnpLabs/Gaufrette) or [Flysystem](https://github.com/FrenkyNet/Flysystem).
3. Works with PSR7 `UploadedFileInterface` objects and with Symfony's `UploadedFile`s (see [integrations](docs/integrations.md)).

Used by [Bolt CMS](https://bolt.cm/)

## Elevator pitch

```php
use Sirius\Upload\Handler as UploadHandler;
$uploadHandler = new UploadHandler('/path/to/local_folder');

// validation rules
$uploadHandler->addRule('extension', ['allowed' => ['jpg', 'jpeg', 'png']], '{label} should be a valid image (jpg, jpeg, png)', 'Profile picture');
$uploadHandler->addRule('size', ['max' => '20M'], '{label} should have less than {max}', 'Profile picture');

$result = $uploadHandler->process($_FILES['picture']); // ex: subdirectory/my_headshot.png

if ($result->isValid()) {
	// do something with the image like attaching it to a model etc
	try {
		$profile->picture = $result->name;
		$profile->save();
		$result->confirm(); // this will remove the .lock file
	} catch (\Exception $e) {
		// something wrong happened, we don't need the uploaded files anymore
		$result->clear();
		throw $e;
	}
} else {
	// image was not moved to the container, where are error messages
	$messages = $result->getMessages();
}
```

## Links

- [documentation](https://www.sirius.ro/php/sirius/upload/)
- [changelog](CHANGELOG.md)
