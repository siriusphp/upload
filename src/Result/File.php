<?php

namespace Sirius\Upload\Result;

use Sirius\Upload\Container\ContainerInterface;

class File
{

    /**
     * Array containing the details of the uploaded file:
     * - name (uploaded name)
     * - original name
     * - tmp_name
     * etc
     *
     * @var array
     */
    protected $file;

    /**
     * The container to which this file belongs to
     * @var \Sirius\Upload\Container\ContainerInterface
     */
    protected $container;

    /**
     * @param $file
     * @param ContainerInterface $container
     */
    public function __construct($file, ContainerInterface $container)
    {
        $this->file = $file;
        $this->container = $container;
    }

    /**
     * Returns if the uploaded file is valid
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->file['name'] && count($this->getMessages()) === 0;
    }

    /**
     * Returns the validation error messages
     *
     * @return array
     */
    public function getMessages()
    {
        if (isset($this->file['messages'])) {
            return $this->file['messages'];
        } else {
            return array();
        }
    }

    /**
     * The file that was saved during process() and has a .lock file attached
     * will be cleared, in case the form processing fails
     */
    public function clear()
    {
        $this->container->delete($this->name);
        $this->container->delete($this->name . '.lock');
        $this->file['name'] = null;
    }

    /**
     * Remove the .lock file attached to the file that was saved during process()
     * This should happen if the form fails validation/processing
     */
    public function confirm()
    {
        $this->container->delete($this->name . '.lock');
    }

    /**
     * File attribute getter
     *
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($this->file[$name])) {
            return $this->file[$name];
        }

        return null;
    }
}
