<?php

namespace Sirius\Upload\Result;


class Collection implements \Iterator, \Countable {
    protected $current = 0;
        
    protected $files = array();
    
    function __construct($files) {
        if (is_array($files)) {
            foreach ($files as $key => $file) {
                $this->files[$key] = new File($file);
            }
        }
    }
    
    function isValid() {
        return count($this->getMessages()) === 0;
    }
    
    function getMessages() {
        $messages = array();
        foreach ($this->files as $key => $file) {
            if (isset($file['messages'])) {
                $messages[$key] = $file['messages'];
            }
        }
        return $messages;
    }
    
    function current() {
        return isset($this->files[$this->current]) ? $this->files[$this->current] : null;
    }
    
    function key() {
        return $this->current;
    }
    
    function rewind() {
        $this->current = 0;
    }
    
    function valid() {
        return isset($this->files[$this->current]);
    }
    
    function next() {
        ++$this->current;
    }
    
    function count() {
        return count($this->files);
    }
}