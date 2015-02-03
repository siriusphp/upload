# Sirius\Upload

[![Build Status](https://travis-ci.org/siriusphp/upload.svg?branch=master)](https://travis-ci.org/siriusphp/upload)
[![Coverage Status](https://coveralls.io/repos/siriusphp/upload/badge.png?branch=master)](https://coveralls.io/r/siriusphp/upload?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/siriusphp/upload/badges/quality-score.png?s=8408e36c1ea02e5f84eec6519d3c3f1972b34e3c)](https://scrutinizer-ci.com/g/siriusphp/upload/)
[![Latest Stable Version](https://poser.pugx.org/siriusphp/upload/version.png)](https://packagist.org/packages/siriusphp/upload)
[![License](https://poser.pugx.org/siriusphp/upload/license.png)](https://packagist.org/packages/siriusphp/upload)

Framework agnostic upload handler library.


## Features

1. Validates files agains usual rules: extension, file size, image size (wdith, height, ratio). It uses [Sirius Validation](http://github.com/siriusphp/validation) for this purpose.
2. Moves valid uploaded files into containers. Containers are usually local folders but you can implement your own or use other filesystem abstractions like [Gaufrette](https://github.com/KnpLabs/Gaufrette) or [Flysystem](https://github.com/FrenkyNet/Flysystem).

## Elevator pitch

```php
use Sirius\Upload\Handler as UploadHandler;
$uploadHandler = new UploadHandler('/path/to/local_folder');

// optional configuration
$uploadHandler->setOverwrite(false); // do not overwrite existing files (default behaviour)
$uploadHandler->setPrefix('subdirectory/append_'); // string to be appended to the file name
$uploadHandler->setAutoconfirm(false); // disable automatic confirmation (default behaviour)

// validation rules
$uploadHandler->addRule('extension', ['allowed' => 'jpg', 'jpeg', 'png'], '{label} should be a valid image (jpg, jpeg, png)', 'Profile picture');
$uploadHandler->addRule('size', ['max' => '20M'], '{label} should have less than {max}', 'Profile picture');
$uploadHandler->addRule('imageratio', ['ratio' => 1], '{label} should be a sqare image', 'Profile picture');

// file name sanitizer, if you don't like the default one which is: preg_replace('/[^A-Za-z0-9\.]+/', '_', $name))
$uploadHandler->setSanitizerCallback(function($name){
	return mktime() . preg_replace('/[^a-z0-9\.]+/', '-', strtolower($name));
});

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

## One aggregator to rule them all

Sometimes your form may upload multiple files to the server. To reduce the number of `process()`, `clear()` and `confirm()` calls you can use an "upload handler aggregate"

```php
use Sirius\Upload\HandlerAggregate as UploadHandlerAggregate;
$uploadHandlerAggregate = new UploadHandlerAggregate();
$uploadHandlerAggregate->addHandler('picture', $previouslyCreatedUploadHandlerForTheProfilePicture);
$uploadHandlerAggregate->addHandler('resume', $previouslyCreatedUploadHandlerForTheResume);

$result = $uploadHandlerAggregate->process($_FILES);

if ($result->isValid()) {
	// do something with the image like attaching it to a model etc
	try {
		$profile->picture = $result['picture']->name;
		$profile->resume = $result['resume']->name;
		$profile->save();
		$result->confirm(); // this will remove the .lock files
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

You can see the aggregator and handlers in action in the [tests/web/index.php](/siriusphp/upload/blob/master/tests/fixitures/sample_file.jpg)

## How it works

1. Uploaded file is validated against the rules. By default the library will check if the upload is valid (ie: no errors during upload)
2. The name of the uploaded file is sanitized (keep only letters, numbers and underscore). You may implement your own sanitization function if you want.
3. If overwrite is not allowed, and a file with the same name already exists in the container, the library will prepend the timestamp to the filename.
4. Moves the uploaded file to the container. It also create a lock file (filename + '.lock') so that we know the upload is not confirmed
5. If something wrong happens in your app and you want to get rid of the uploaded file you can `clear()` the uploaded file which will remove the file and its `.lock` file. Only files that have a corresponding `.lock` file attached can be cleared
6. If everything is in order you can `confirm` the upload. This will remove the `.lock` file attached to the upload file.

#### What is "locking"?

Usually, an application accepts file uploads to store them for future use (product images, people resumes etc). But from the time an uploaded file is moved to its container (the folder on disk, an S3 bucket) until the actual data is saved there are things that can go wrong (eg: the database goes down and the uploaded image cannot be attached to a model).
The `locking` functionality was implemented for this reason. So, whenever a file is uploaded, on the same location another file with the `.lock` extension is created. This file is removed when the upload is confirmed.

Worst case scenario (when the system breaks down so you cannot execute the `clear()` method) you will be able to look into the container in "spot" the unused files. This feature must be used with care:

1. If you want to take advantage of this feature you must use `confirm` or you will end up with `.lock` files everywhere.
2. If you don't like it, use `$uploadHandler->setAutoconfirm(true)` and all uploaded files will automatically confirmed

## Using different containers

If you want to store uploaded files in different locations your containers must implement the `Sirius\Upload\Container\ContainerInterface`.

```php
$amazonBucket = new AmazonBucket();
$container = new AmazonContainer($amazonBucket);
$uploadHandler = new UploadHandler($container);
```
You can easily create upload containers on top of [Gaufrette](https://github.com/KnpLabs/Gaufrette) or [Flysystem](https://github.com/FrenkyNet/Flysystem).

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
