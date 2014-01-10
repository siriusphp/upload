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
     * Returns the result of the upload operation
     * It is the name of the file or a list of files as stored in the container
     * 
     * @return boolean|string|array
     */
    public function getResult();

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
    
    /**
     * Returns the error messages associatied with the upload operation
     */
    public function getMessages();
}