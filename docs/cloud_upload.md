---
title: Upload into the cloud
---

# Upload into the cloud

If you want to store uploaded files in different locations your containers must implement the `Sirius\Upload\Container\ContainerInterface`.

The example below is not based on real-life code, it's for illustration purposes only.

```php
$amazonBucket = new AmazonBucket();
$container = new AmazonContainer($amazonBucket);
$uploadHandler = new UploadHandler($container);
```

You can easily create upload containers on top of [Gaufrette](https://github.com/KnpLabs/Gaufrette) or [Flysystem](https://github.com/FrenkyNet/Flysystem).