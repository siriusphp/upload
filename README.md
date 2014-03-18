#Sirius\Upload

Framework agnostic upload handler library.

## Features

1. Validates files agains usual rules: extension, file size, image size (wdith, height, ratio)
2. Moves valid uploaded files into containers. Containers are usually local folders but you can implement your own or use other filesystem abstractions like [Gaufrette](https://github.com/KnpLabs/Gaufrette) or [Flysystem](https://github.com/FrenkyNet/Flysystem).

## Elevator pitch

```php
use Sirius\Upload\Handler as UploadHandler;
$uploadHandler = new UploadHandler('/path/to/local_folder', 'subdirectory', false /* do not overwrite existing files in container*/);
$uploadHandler->getValidator()->add('extension', ['allowed' => 'jpg', 'jpeg', 'png'], '{label} should be a valid image (jpg, jpeg, png)', 'Profile picture');
$uploadHandler->getValidator()->add('filesize', ['max' => '20M'], '{label} should have less than {max}', 'Profile picture');
$uploadHandler->getValidator()->add('imageratio', ['ratio' => 1], '{label} should be a sqare image', 'Profile picture');

$savedImage = $uploadHandler->process($_FILES['picture']); // ex: subdirectory/my_headshot.png

if ($savedImage) {
	// do something with the image like attaching it to a model etc
	try {
		$profile->picture = $savedImage;
		$profile->save();
		$uploadHandler->confirm($savedFile); // this will remove the temporary marker attached to that file
	} catch (\Exception $e) {
		// something wrong happend, we don't need the uploaded picture anymore
		$uploadHandler->clear($savedImage);
		throw $e;
	}
} else {
	// image was not saved, most likely due to the file not being valid
	$messages = $uploadHandler->getMessages();
}
```

## How it works

1. Uploaded file is validated agains the rules. By default the library will check if the upload is valid (ie: no errors during upload)
2. The name of the uploaded file is sanitized (keep only letters, numbers and underscore than lowercase). You may implement your own sanitization function if you want.
3. If overwrite is not allowed, and a file with the same name already exists in the container, the library tries to find an avaiable file name based on the original file name.
4. Moves the uploaded file to the container. It also create a lock file (filename + '.lock') so that we know the upload is not confirmed
5. If something wrong happens in your app and you want to get rid of the uploaded file you can `clear()` the uploaded file which will remove the file and its `.lock` file. Only files that have a coresponding `.lock` file attached can be cleared
6. If everything is in order you can `confirm` the upload. This will remove the `.lock` file attached to the upload file.

### What is "locking"?

Usualy application accept file uploads to store them for future use (product images, people resumes etc). But from the time an uploaded file is moved to its container until the actual data is saved there are things that can go wrong (eg: the database goes down).
For this reason the `locking` functionality was implemented. This way, even if you're not able to execute the `clear()` method you will be able to look into the container in "spot" the unused files. This feature must be used with care

1. If you want to take advantage of this feature you must use `confirm`
2. If you don't like it, use `$uploadHandler->setAutoConfirm(true)` and all uploaded files will automatically confirmed

## Using different containers

If you want to store uploaded files in different locations your containers must implement the `Sirius\Upload\Container\ContainerInterface`.

```php
$amazonBucket = new AmazonBucket();
$container = new AmazonContainer($amazonBucket);
$uploadHandler = new UploadHandler($container, 'prefix_for_files', true /* allow overwrites/*);
```
You can easily create upload containers on top of [Gaufrette](https://github.com/KnpLabs/Gaufrette) or [Flysystem](https://github.com/FrenkyNet/Flysystem).

## Important notes

##### 1. The library makes no assumptions about the "web reachability" of the uploaded file. 

Most of the times once you have a valid upload the new file will be reachable on the internet. You may upload your files to `/var/www/public/images/users/` and have the files accessible at `//cdn.domain.com/users/`. It's up to you to make your app work with the result of the upload

##### 2. You can handle multiple uploads at once if they have the same name

If you upload multiple files with the same name (eg: `<input type="file" name="pictures[]">`) but you have to keep in mind that the `process()` and `getMessages()` methods will return arrays

```php
$files = $uploadHandler->process($_FILES['pictures']);
// may return
array(
	'/subdirectory/valid_file.jpg',
	false // because the the file was not valid
);
// while
$messages = $uploadHandler->getMessages();
// will return
array(
	'1' => 'File type not accepted'
);
```

In this case the library normalizes the `$_FILES` array as PHP messes up the upload array.
It is up to you to decide what you want to do when some files fail to upload (eg: keep the valid files and continue or display error messages for the invalid images)

