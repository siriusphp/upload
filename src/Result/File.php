<?php
declare(strict_types=1);

namespace Sirius\Upload\Result;

use Sirius\Upload\Container\ContainerInterface;

/**
 * @property string $name
 */
class File implements ResultInterface
{

    /**
     * Array containing the details of the uploaded file:
     * - name (uploaded name)
     * - original name
     * - tmp_name
     * etc
     *
     * @var array<string, mixed>
     */
    protected array $file;

    /**
     * The container to which this file belongs to
     */
    protected ContainerInterface $container;

    /**
     * @param array<string, mixed> $file
     * @param ContainerInterface $container
     */
    public function __construct(array $file, ContainerInterface $container)
    {
        $this->file      = $file;
        $this->container = $container;
    }

    /**
     * Returns if the uploaded file is valid
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->file['name'] && count($this->getMessages()) === 0;
    }

    /**
     * Returns the validation error messages
     *
     * @return array<string, mixed>
     */
    public function getMessages(): array
    {
        if (isset($this->file['messages'])) {
            return $this->file['messages'];
        } else {
            return [];
        }
    }

    /**
     * The file that was saved during process() and has a .lock file attached
     * will be cleared, in case the form processing fails
     */
    public function clear(): void
    {
        $this->container->delete($this->name);
        $this->container->delete($this->name . '.lock');
        $this->file['name'] = null;
    }

    /**
     * Remove the .lock file attached to the file that was saved during process()
     * This should happen if the form fails validation/processing
     */
    public function confirm(): void
    {
        $this->container->delete($this->name . '.lock');
    }

    public function __get(string $name): mixed
    {
        if (isset($this->file[$name])) {
            return $this->file[$name];
        }

        return null;
    }
}
