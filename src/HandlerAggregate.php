<?php
namespace Sirius\Upload;


use Sirius\Upload\Util\Arr;
use Sirius\Validation\ErrorMessage;

class HandlerAggregate implements \IteratorAggregate{

    protected $handlers = array();

    function addHandler($selector, Handler $handler) {
        $handler->setErrorMessagePrototype
        $this->handlers[$selector] = $handler;
        return $this;
    }

    function removeHandler($selector) {
        if (isset($this->handlers[$selector])) {
            unset($this->handlers[$selector]);
        }
        return $this;
    }

    function removeHandlers() {
        $this->handlers = array();
        return $this;
    }


    function process($files) {
        $result = array();
        foreach ($this->handlers as $selector => $handler) {
            /* @var $handler Handler */
            $selectedFiles = Arr::getBySelector($files, $selector);

            if (!is_array($selectedFiles) || empty($selectedFiles)) {
                continue;
            }

            foreach ($selectedFiles as $path => $file) {
                $result[$path] = $handler->proces($file);
            }
        }

        return $result;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Retrieve an external iterator
     *
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     *       <b>Traversable</b>
     */
    public function getIterator()
    {
        return $this->handlers;
    }
}