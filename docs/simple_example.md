---
title: Simple upload example
---

# Simple example

### Initialize the upload handler class

Let's consider a simple contact form that has the following field: `name`, `email`, `phone` and `message`.

```php
use Sirius\Upload\Handler as UploadHandler;
$uploadHandler = new UploadHandler('/path/to/local_folder');

// set up the validation rules
$uploadHandler->addRule('extension', ['allowed' => 'jpg', 'jpeg', 'png'], '{label} should be a valid image (jpg, jpeg, png)', 'Profile picture');
$uploadHandler->addRule('size', ['max' => '20M'], '{label} should have less than {max}', 'Profile picture');
$uploadHandler->addRule('imageratio', ['ratio' => 1], '{label} should be a sqare image', 'Profile picture');

```

### Process the upload

```php

$result = $uploadHandler->process($_FILES['picture']); // ex: subdirectory/my_headshot.png

if ($result->isValid()) {
    try {

        // do something with the image like attaching it to a model etc
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