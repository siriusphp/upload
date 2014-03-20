<?php
namespace Sirius\Upload;

use Sirius\Upload\Container\ContainerInterface;
use Sirius\Upload\Container\Local as LocalContainer;
use Sirius\Upload\Exception\InvalidContainerException;

class Handler implements UploadHandlerInterface {
    /**
     * @var ContainerInterface
     */
    protected $container;
    
    /**
     * Prefix to be added to the file.
     * It can be a subfolder or just a string to be used as prefix
     * 
     * @var string
     */
    protected $prefix = '';
    
    /**
     * When uploading a file that has the same name as a file that is
     * already in the container should it overwrite it or use another name
     * 
     * @var boolean
     */
    protected $overwrite = false;

    /**
     * Whether or not the uploaded files are auto confirmed
     * 
     * @var boolean
     */
    protected $autoConfirm = false;

    /**
     * Whether or not there was only one file being uploaded
     * 
     * @var boolean
     */
    protected $isSingle = true;

    /**
     * The list of validation messages associated with the upload
     *
     * @var array
     */
    protected $messages = array();
    
    function __construct($directoryOrContainer, $prefix = '', $overwrite = false) {
        $container = $directoryOrContainer;
    	if (is_string($directoryOrContainer)) {
    		$container = new LocalContainer($directoryOrContainer);
    	}
    	if (!$container instanceof ContainerInterface) {
            throw new InvalidContainerException('Destination container for uploaded files is missing');
        }
        $this->container = $container;
        $this->prefix = (string)$prefix;
        $this->overwrite = (bool)$overwrite;
    }
    
    function setOverwrite(bool $overwrite) {
    	$this->overwrite = $overwrite;
    	return $this;
    }
    
    function setPrefix(string $prefix) {
    	$this->prefix = $prefix;
    	return $this;
    }
    
    function setAutoconfirm(bool $autoConfirm) {
    	$this->autoConfirm = $autoConfirm;
    	return $this;
    }
    
    function process($files = array()) {
        $this->isSingle = isset($files['name']) && isset($files['name']);
        
        $this->files = $this->normalizeFiles($files);
        foreach ($this->files as $k => $file) {
            $files[$k] = $this->processSingleFile($file);
        }
        return empty($this->getMessages());
    }
    
    function clear($file) {
        $files = is_array($file) ? $file : array($file);
        foreach ($files as $file) {
            if ($this->container->has($file . '.lock')) {
                $this->container->delete($file);
                $this->container->delete($file . '.lock');
            }
        }
    }
    
    function confirm($file) {
        $files = is_array($file) ? $file : array($file);
        foreach ($files as $file) {
            $this->container->delete($file . '.lock');
        }
    }

    function getResult() {
        $result = false;
        if ($this->isSingle) {
            $result = isset($this->files[0]) ? $this->files[0]['uploaded_name'] : false;
        } else {
            $result = array();
            foreach ($this->files as $file) {
                $result[] = $file['uploaded_name'];
            }
        }
        return $result;
    }

    function getMessages() {
        return $this->messages;
    }
    
    protected function processSingleFile($file) {
        
    }
    
    protected function normalizeFiles($files) {
        // wrong format, go away
        if (!is_array($files) || !isset($files['name'])) {
            return array();
        }
        $result = array();
        // we have list of files, which PHP messes up
        if (is_array($files['name'])) {
            foreach ($files['name'] as $k => $v) {
                $result[$k] = array(
                    'name' => $this->fixUploadedFileName($files['name'][$k]),
                    'type' => $files['type'][$k],
                    'size' => $files['size'][$k],
                    'error' => $files['error'][$k],
                    'tmp_name' => $files['tmp_name'][$k]
                );
            }
            $files = $result;
        // we have a single file
        } elseif (isset($files['name'])) {
            $files['name'] = $this->fixUploadedFileName($files['name']);
            $result = array($files);
        // we have a list of files which are in correct format
        } elseif (isset($files[0]) && isset($files[0]['name'])) {
            foreach ($files as $k => $file) {
                $files[$k]['name'] = $this->fixUploadedFileName($file['name']);
            }
            $result = $files;
        }
        return $result;
    }
    
    protected function fixUploadedFileName($name) {
        $name = preg_replace('/[^a-z0-9\.]+/', '_', strtolower($name));
        return preg_replace('/[_]+/', '_', $name);
    }
}