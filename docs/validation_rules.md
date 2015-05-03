---
title: File upload validation rules
---

# Upload validation rules

For validating the uploads the library uses the [Sirius\Validation](https://www.github.com/siriushp/validation) library.

You add validation rules using the following command:

```php
$uploadHandler->addRule($ruleName, $ruleOptions, $errorMessage, $fieldLabel);
```

The following validation rules are available.

### Upload validators

#### extension

```php
$uploadHandler->addRule('extension', array('allowed' => 'doc,pdf'));
// or any other format that is understandable by the Sirius\Validation library, like
$uploadHandler->addRule('extension', 'allowed=doc,pdf', '{label} should be a DOC or PDF file', 'The resume');
```

#### image

```php
$uploadHandler->addRule('image', 'allowed=jpg,png');
```

#### size

The `size` option can be a number or a string like '10K', '0.5M' or '1.3G` (default: 2M)
```php
$uploadHandler->addRule('size', 'size=2M');
```

#### imagewidth

The options `min` and `max` are presented in pixels
```php
$uploadHandler->addRule('imagewidth', 'min=100&max=2000');
```

#### imageheight

The options `min` and `max` are presented in pixels
```php
$uploadHandler->addRule('imageheight', 'min=100&max=2000');
```

#### imageratio

The option `ratio` can be a number (eg: 1.3) or a ratio-like string (eg: 4:3, 16:9).
The option `error_margin` specifies how much the image is allowed to deviate from the target ratio. Default value is 0
```php
$uploadHandler->addRule('imageratio', 'ratio=4:3&error_margin=0.01');
```

*Note!* The upload validators use only the `tmp_name` and `name` values to perform the validation