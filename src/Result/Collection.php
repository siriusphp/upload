<?php
declare(strict_types=1);

namespace Sirius\Upload\Result;

use Sirius\Upload\Container\ContainerInterface;

class Collection extends \ArrayIterator implements ResultInterface
{
    /**
     * @param array<int, mixed> $files
     */
    public function __construct(array $files = [], ContainerInterface $container = null)
    {
        $filesArray = [];
        if ($container && ! empty($files)) {
            foreach ($files as $key => $file) {
                $filesArray[$key] = new File($file, $container);
            }
        }
        parent::__construct($filesArray);
    }

    public function clear(): void
    {
        foreach ($this as $file) {
            /* @var $file \Sirius\Upload\Result\File */
            $file->clear();
        }
    }

    public function confirm(): void
    {
        foreach ($this as $file) {
            /* @var $file \Sirius\Upload\Result\File */
            $file->confirm();
        }
    }

    public function isValid(): bool
    {
        foreach ($this->getMessages() as $messages) {
            if ($messages) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getMessages(): array
    {
        $messages = [];
        foreach ($this as $key => $file) {
            /* @var $file \Sirius\Upload\Result\File */
            $messages[$key] = $file->getMessages();
        }

        return $messages;
    }
}
