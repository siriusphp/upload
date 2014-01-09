<?php
namespace Sirius\Upload;

use Sirius\Upload\Container\ContainerInterface;
use Sirius\Upload\Container\Local as LocalContainer;
use Sirius\Upload\Exception\InvalidContainerException;

class Handler {
    /**
     * @var ContainerInterface
     */
    protected $container;
    
    protected $webLocation = '/';
    
    protected $prefix = '';
    
    protected $overwrite = false;
    
    function __construct($directoryOrContainer, $webLocation = '/', $prefix = '', $overwrite = false) {
        if ($directoryOrContainer instanceof ContainerInterface) {
            $this->setContainer($directoryOrContainer);
        } elseif (is_string($directoryOrContainer)) {
            $this->setContainer(new LocalContainer($directoryOrContainer));
        }
        if (!$this->container) {
            throw new InvalidContainerException('Destination container for uploaded files is missing');
        }
        if (!$this->container->isWritable()) {
            throw new InvalidContainerException('Destination container for uploaded files is not writable');
        }
        $this->webLocation = (string)$webLocation;
        $this->prefix = (string)$prefix;
        $this->overwrite = (bool)$overwrite;
    }
    
    function setContainer(ContainerInterface $container) {
        $this->container = $container;
        return $this;
    }
    
    function getContainer() {
        return $this->container;
    }
    
    function process($files = array()) {
        $files = $this->normalizeFiles($files);
    }
    
    protected function normalizeFiles($files) {
        if (!is_array($files) || !isset($files['name'])) {
            return array();
        }
        if (is_array($files['name'])) {
            $result = array();
            foreach ($files['name'] as $k => $v) {
                $result[$k] = array(
                    'name' => $this->fixUploadedFileName($files['name'][$k]),
                    'type' => $files['type'][$k],
                    'size' => $files['size'[$k],
                    'error' => $files['error'][$k],
                    'tmp_name' => $files['tmp_name'][$k]
                );
            }
            $files = $result;
        } else {
            $files['name'] = $this->fixUploadedFileName($files['name']);
        }
        return $files;
    }
    
    protected function fixUploadedFileName($name) {
        return $name;
    }
}