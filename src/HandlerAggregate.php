<?php
namespace Sirius\Upload;


use Sirius\Upload\Result\Collection;
use Sirius\Upload\Util\Arr;

class HandlerAggregate implements \IteratorAggregate
{

    protected $handlers = array();

    function addHandler($selector, Handler $handler)
    {
        $this->handlers[$selector] = $handler;
        return $this;
    }

    function process($files)
    {
        $result = new Collection();
        foreach ($this->handlers as $selector => $handler) {
            /* @var $handler Handler */
            $selectedFiles = Arr::getBySelector($files, $selector);

            if (!$selectedFiles || !is_array($selectedFiles) || empty($selectedFiles)) {
                continue;
            }

            foreach ($selectedFiles as $path => $file) {
                if (is_array($file)) {
                    $result[$path] = $handler->process($file);
                }
            }
        }

        return $result;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Retrieve an external iterator
     *
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return \Traversable An instance of an object implementing <b>Iterator</b> or
     *       <b>Traversable</b>
     */
    public function getIterator()
    {
        return $this->handlers;
    }
}
