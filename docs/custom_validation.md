---
title: Custom upload validation
---

# Custom validation

If you need implement specific file upload validation rules remember to look at the documentation for the [Sirius\Validation](https://www.gihub.com/siriusphp/validation) library.

For example if you have a system where the users have upload quotas and you want to make sure that they don't exceed their allocated quota you ca do the following

```php

function check_user_quota($file) {
    return User::instance()->getRemainingQuota() < $file['size'];
}

$uploadHandler->addRule('callback', array('callback' => 'check_user_quota'), 'Sorry, but you don\'t have enough space to upload this file');
```

# Use a custom validator

You can inject a `Sirius\Validation\ValueValidator` upon construction. The example below assumes there is a dependency injection container.

```php
$ruleFactory = $container->get('Sirius\Validation\RuleFactory');
$ruleFactory->register('quota_is_met', 'MyApp\Validation\Rule\FileQuota');

$valueValidator = $container->get('Sirius\Validation\ValueValidator');

$handler = new Sirius\Upload\Handler('/path/to/dir', array(), $valueValidator);

$handler->addRule('quota_is_met');
```