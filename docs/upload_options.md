---
title: Upload options
---

#Upload options

There are a few options you can choose to use while using the Sirius\Upload library

## Configure the uploader upon construction

```php
user \Sirius\Upload\Handler;
$uploadHandler = Handler('/path/to/dir', array(
    Handler::OPTION_AUTOCONFIRM => true,
    Handler::OPTION_OVERWRITE => true,
    Handler::OPTION_PREFIX => '/subdirectory/' . time() . '_',    
));
```

## Set options during execution

#### Overwrite existing files

A file is saved into the destination folder under it's own name. And there is a chance a file with that name might already be there.
You can choose to overwrite the existing file if you want. The library doesn't overwrite files by default.

```php
$uploadHandler->setOverwrite(true);
```

#### Auto-confirm uploads

As explained in the "[file locking](file_locking.md)" section, the uploaded files are `locked` and you have to manually `confirm()` the uploads to unlock them.
You can override this default behaviour via:

```php
$uploadHandler->setAutoconfirm(false);
```

#### Prefixing uploads

Sometimes you want to set up a prefix for your uploaded files (which can be a subdirectory, a timestamp etc). You can do this via:

```php
$uploadHandler->setPrefix('subdirectory/append_');
```

You can use a function/callback as the prefix

```php
function upload_prefix($file_name) {
    return substr(md5($file_name), 0, 5) . '/';
}
$uploadHandler->setPrefix('upload_prefix');
```

## Filename sanitization

By default the library cleans up the name of the uploaded file by preserving only letters and numbers. If you want something else you set up a sanitizer callback:

```php
$uploadHandler->setSanitizerCallback(function($name){
    return mktime() . preg_replace('/[^a-z0-9\.]+/', '-', strtolower($name));
});
```
