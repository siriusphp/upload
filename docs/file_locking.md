---
title: File locking during uploads
---

# What is 'file locking'?

Usually, an application accepts file uploads to store them for future use (product images, people resumes etc). 
But from the time an uploaded file is moved to its container (the folder on disk, an S3 bucket) until the actual data is saved there are things that can go wrong (eg: the database goes down and the uploaded image cannot be attached to a model).

The `locking` functionality was implemented for this reason. Whenever a file is uploaded, on the same location another file with the `.lock` extension is created. This file is removed when the upload is confirmed.

Worst case scenario (when the system breaks down so you cannot even execute the `clear()` method) you will be able to look into the upload container (local directory, S3 bucket) and "spot" the unused files. 

If you want to take advantage of this feature you are **REQUIRED** use `confirm()` or you will end up with `.lock` files everywhere.

If you don't like it, use `$uploadHandler->setAutoconfirm(true)` and all uploaded files will automatically confirmed.

