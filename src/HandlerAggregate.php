<?php
declare(strict_types=1);

namespace Sirius\Upload;

use Sirius\Upload\Result\Collection;
use Sirius\Validation\Util\Arr;

class HandlerAggregate implements \IteratorAggregate
{
    /**
     * @var array<string, Handler> $handlers
     */
    protected array $handlers = [];

    /**
     * Adds a handler on the aggregate
     */
    public function addHandler(string $selector, Handler $handler): self
    {
        $this->handlers[$selector] = $handler;

        return $this;
    }

    /**
     * @param array<string, mixed> $files
     *
     * @return Result\Collection
     */
    public function process(mixed $files): mixed
    {
        $result = new Collection();
        foreach ($this->handlers as $selector => $handler) {
            /* @var $handler Handler */
            $selectedFiles = Arr::getBySelector($files, $selector);

            if (empty($selectedFiles)) {
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

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->handlers);
    }
}
