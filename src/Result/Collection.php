<?php

namespace Sirius\Upload\Result;


class Collection extends \ArrayIterator {
    protected $current = 0;

    protected $files = array();

    function __construct($files) {
        $filesArray = array();
        if (is_array($files)) {
            foreach ($files as $key => $file) {
                $filesArray[$key] = new File($file);
            }
        }
        parent::__construct($filesArray);
    }

    function isValid() {
        foreach ($this->getMessages() as $key => $messages) {
            if ($messages) {
                return false;
            }
        }
        return true;
    }

    function getMessages() {
        $messages = array();
        foreach ($this as $key => $file) {
            $messages[$key] = $file->getMessages();
        }
        return $messages;
    }

}