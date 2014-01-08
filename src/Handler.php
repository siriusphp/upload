<?php
namespace Sirius\Upload;

use Sirius\Upload\FileSystem\Local;

class Handler {
    protected $filesystem;
    
    protected $destinationDirectory;
    
    protected $webLocation = '/';
    
    protected $prefix = '';
    
    protected $overwrite = false;
    
    function __construct($destinationDirectory, $webLocation = '/', $prefix = '', $overwrite = false) {
        $this->destinationDirectory = (string)$destinationDirectory;
        $this->webLocation = (string)$webLocation;
        $this->prefix = (string)$prefix;
        $this->overwrite = (bool)$overwrite;
    }
    
    function setFilesystem(FilesystemInterface $filesystem) {
        
        return $this;
    }
    
    function getFilesystem() {
        if (!$this->filesystem) {
            $this->filesystem = new Local; 
        }
        $this->filesystem->setBaseDirectory($this->destinationDirectory);
        return $this->filesystem;
    }
    
    function process($files = array()) {
        
    }
    
    
}