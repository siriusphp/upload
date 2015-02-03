<?php

namespace Sirius\Upload\Result;

use Sirius\Upload\Container\ContainerInterface;

class Collection extends \ArrayIterator
{
    public function __construct($files = array(), ContainerInterface $container = null)
    {
        $filesArray = array();
        if (is_array($files) && !empty($files)) {
            foreach ($files as $key => $file) {
                $filesArray[$key] = new File($file, $container);
            }
        }
        parent::__construct($filesArray);
    }

    public function clear()
    {
        foreach ($this as $file) {
            /* @var $file \Sirius\Upload\Result\File */
            $file->clear();
        }
    }

    public function confirm()
    {
        foreach ($this as $file) {
            /* @var $file \Sirius\Upload\Result\File */
            $file->confirm();
        }
    }

    public function isValid()
    {
        foreach ($this->getMessages() as $messages) {
            if ($messages) {
                return false;
            }
        }

        return true;
    }

    public function getMessages()
    {
        $messages = array();
        foreach ($this as $key => $file) {
            /* @var $file \Sirius\Upload\Result\File */
            $messages[$key] = $file->getMessages();
        }

        return $messages;
    }

}
