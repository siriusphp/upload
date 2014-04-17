<?php

namespace Sirius\Upload;

interface UploadHandlerInterface
{

    /**
     * This function will process the files received from $_FILES,
     * validate them and save them into the container.
     *
     * Along with the file saved into the container a .lock file should
     * be added by the container save() method so, in case the form is
     * not validated, the uploaded file will be removed.
     *
     * @param array $files
     */
    public function process($files = array());

}
