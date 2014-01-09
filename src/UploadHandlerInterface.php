<?php

namespace Sirius\Upload;

interface UploadHandlerInterface {
    
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
    
    /**
     * The file that was saved during process() and has a .lock file attached
     * will be cleared, in case the form processing fails
     * 
     * @param string $file
     */
    public function clear($file);
    
    /**
     * Remove the .lock file attached to the file that was saved during process()
     * This should happen if the form fails validation/processing
     *  
     * @param string $file
     */
    public function confirm($file);
    
}