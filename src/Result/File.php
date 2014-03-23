<?php

namespace Sirius\Upload\Result;

class File {
    
    protected $file;
    
    function __construct($file) {
        $this->file = $file;
    }
    
    function isValid() {
        return count($this->getMessages()) === 0;        
    }
    
    function getMessages() {
        return $this->file['messages'];
    }
    
    function __get($name) {
        if (isset($this->file[$name])) {
            return $this->file[$name];
        }
    }
}