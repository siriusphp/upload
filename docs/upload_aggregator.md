---
title: The upload aggregator
---

# The upload aggregator

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

You can see the aggregator and handlers in action in the [tests/web/index.php](https://www.github.com/siriusphp/upload/blob/master/tests/web/index.php)
