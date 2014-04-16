<?php

namespace Sirius\Upload\Result;

use Sirius\Upload\Container\ContainerInterface;

class Collection extends \ArrayIterator
{
    function __construct($files = array(), ContainerInterface $container = null)
    {
        $filesArray = array();
        if (is_array($files) && !empty($files)) {
            foreach ($files as $key => $file) {
                $filesArray[$key] = new File($file, $container);
            }
        }
        parent::__construct($filesArray);
    }

    function clear() {
        foreach ($this as $file) {
            $file->clear();
        }
    }

    function confirm() {
        foreach ($this as $file) {
            $file->confirm();
        }
    }

    function isValid()
    {
        foreach ($this->getMessages() as $key => $messages) {
            if ($messages) {
                return false;
            }
        }
        return true;
    }

    function getMessages()
    {
        $messages = array();
        foreach ($this as $key => $file) {
            $messages[$key] = $file->getMessages();
        }
        return $messages;
    }

}
