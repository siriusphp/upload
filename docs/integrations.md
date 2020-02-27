---
title: Integrations | Sirius Upload
---

# Integrations with other libraries

## PSR-7's UploadedFileInterface

If you are using a library like [Laminas Diactoros](https://github.com/laminas/laminas-diactoros) that can provide an array of objects that implement `UploadedFileInterface` from the PSR-7 standard you can do the following

```php
/** @var Sirius\Upload\Handler $uploadHandler */
/** @var Laminas\Diactoros\ServerRequest $request */

$result = $uploadHandler->process($request->getUploadedFiles());
```

## Symfony's UploadedFile

If you integrate this into a project that uses Symfony's HTTP Foundation component you can do the following:

```php
/** @var Sirius\Upload\Handler $uploadHandler */
/** @var Symfony\Component\HttpFoundation\Request $request */

$result = $uploadHandler->process($request->files->all());
```


