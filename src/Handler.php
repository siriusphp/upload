<?php
namespace Sirius\Upload;

use Sirius\Upload\Container\ContainerInterface;
use Sirius\Upload\Container\Local as LocalContainer;
use Sirius\Upload\Exception\InvalidContainerException;
use Sirius\Validation\ErrorMessage;
use Sirius\Validation\ValueValidator;

class Handler implements UploadHandlerInterface {
    // constants for constructor options
    const OPTION_PREFIX = 'prefix';
    const OPTION_OVERWRITE = 'overwrite';
    const OPTION_AUTOCONFIRM = 'autoconfirm';
    
    // constants for validation rules
    const RULE_EXTENSION = 'extension';
    const RULE_SIZE = 'size';
    const RULE_IMAGE = 'image';
    const RULE_IMAGE_HEIGHT = 'imageheight';
    const RULE_IMAGE_WIDTH = 'imagewidth';
    const RULE_IMAGE_RATIO = 'imageratio';
    
    /**
     * @var ContainerInterface
     */
    protected $container;
    
    /**
     * Prefix to be added to the file.
     * It can be a subfolder (if it ends with '/', a string to be used as prefix) 
     * or a callback that returns a string
     * 
     * @var string|callback
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
    protected $autoconfirm = false;
    
    /**
     * @var Sirius\Validation\ValueValidator
     */
    protected $validator;

    function __construct($directoryOrContainer, ErrorMessage $errorMessagePrototype = null, $options = array()) {
        $container = $directoryOrContainer;
    	if (is_string($directoryOrContainer)) {
    		$container = new LocalContainer($directoryOrContainer);
    	}
    	if (!$container instanceof ContainerInterface) {
            throw new InvalidContainerException('Destination container for uploaded files is not valid');
        }
        $this->container = $container;
        
        // create the error message prototype if it does not exist
        if (!$errorMessagePrototype) {
            $errorMessagePrototype = new ErrorMessage;
        }
        
        // create the validator
        $this->validator = new ValueValidator(null, $errorMessagePrototype);
        
        
        // set options
        $availableOptions = array(
        	static::OPTION_PREFIX => 'setPrefix',
            static::OPTION_OVERWRITE => 'setOverwrite',
            static::OPTION_AUTOCONFIRM => 'setAutoconfirm'
        );
        foreach ($availableOptions as $key => $method) {
            if (isset($options[$key])) {
                $this->{$method}($options[$key]);
            }
        }
    }
    
    /**
     * Enable/disable upload overwrite
     * 
     * @param bool $overwrite
     * @return \Sirius\Upload\Handler
     */
    function setOverwrite($overwrite) {
    	$this->overwrite = (bool) $overwrite;
    	return $this;
    }
    
    /**
     * File prefix for the upload. Can be
     * - a folder (if it ends with /)
     * - a string to be used as prefix
     * - a function that returns a string
     * 
     * @param string|callable $prefix
     * @return \Sirius\Upload\Handler
     */
    function setPrefix($prefix) {
    	$this->prefix = $prefix;
    	return $this;
    }
    
    /**
     * Enable/disable upload autoconfirmation
     * Autoconfirmation does not require calling `confirm()`
     * 
     * @param boolean $autoconfirm
     * @return \Sirius\Upload\Handler
     */
    function setAutoconfirm($autoconfirm) {
    	$this->autoconfirm = (bool) $autoconfirm;
    	return $this;
    }
    
    /**
     * Add validation rule (extension|size|width|height|ratio)
     * 
     * @param string $name
     * @param mixed $options
     * @param string $errorMessageTemplate
     * @param string $label
     * @return \Sirius\Upload\Handler
     */
    function addRule($name, $options = null, $errorMessageTemplate = null, $label = null) {
        $predefinedRules = array(
        	static::RULE_EXTENSION,
            static::RULE_IMAGE,
            static::RULE_SIZE,
            static::RULE_IMAGE_WIDTH,
            static::RULE_IMAGE_HEIGHT,
            static::RULE_IMAGE_RATIO
        );
        // convert to a name that is known by the default RuleFactory
        if (in_array($name, $predefinedRules)) {
            $name = 'upload' . $name;
        }
        $this->validator->add($name, $options, $errorMessageTemplate, $label);
        return $this;
    }
    
    function process($files = array()) {
        $isSingle = isset($files['name']) && !is_array($files['name']);

        $files = $this->normalizeFiles($files);
        
        foreach ($files as $k => $file) {
            $files[$k] = $this->processSingleFile($file);
        }
        
        if ($isSingle) {
            return new Result\File($files[0]);
        }
        return new Result\Collection($files);
    }
    
    function clear($result) {
        if ($result instanceof Result\Collection) {
            return $this->clearCollection($result);
        }
        if ($result instanceof Result\File) {
            return $this->clearFile($result);
        }
        throw new Exception\InvalidResultException('Result passed for clearing is not valid');
    }
    
    protected function clearFile(Result\File $file) {
        $this->container->delete($file->name);
        $this->container->delete($file->name . '.lock');
        return true;
    }
    
    protected function clearCollection(Result\Collection $collection) {
        foreach ($collection as $file) {
            $this->clearFile($file);
        }
        return true;
    }
    
    function confirm($result) {
        if ($result instanceof Result\Collection) {
            return $this->confirmCollection($result);
        }
        if ($result instanceof Result\File) {
            return $this->confirmFile($result);
        }
        throw new Exception\InvalidResultException('Result passed for confirmation is not valid');
    }
    
    protected function confirmFile(Result\File $file) {
        $this->container->delete($file->name . '.lock');
        return true;
    }
    
    protected function confirmCollection(Result\Collection $collection) {
        foreach ($collection as $file) {
            $this->confirmFile($file);
        }
        return true;
    }
    

    /**
     * Processes a single uploaded file
     * - sanitize the name
     * - validates the file
     * - if valid, moves the file to the container
     * 
     * @param array $file
     * @return array
     */
    protected function processSingleFile(array $file) {
        // sanitize the file name
        $file['name'] = $this->sanitizeFileName($file['name']);
        
        $file = $this->validateFile($file);
        // if there are messages the file is not valid
        if (isset($file['messages']) && $file['messages']) {
            return $file;
        }
        
        // add the prefix
        $prefix = '';
        if (is_callable($this->prefix)) {
            $prefix = (string) call_user_func($this->prefix, $file['name']);
        } elseif (is_string($this->prefix)) {
            $prefix = (string) $this->prefix;
        }
        
        // if overwrite is not allowed, check if the file is already in the container
        if (!$this->overwrite) {
            if ($this->container->has($prefix . $file['name'])) {
                // add the timestamp to ensure the file is unique
                // method is not bulletproof but it's pretty safe
                $file['name'] = time() . '_' . $file['name'];
            }
        }

        // attempt to move the uploaded file into the container
        if (!$this->container->moveUploadedFile($file['tmp_name'], $prefix . $file['name'])) {
            $file['name'] = false;
            return $file;
        }
        
        $file['name'] = $prefix . $file['name'];
        // create the lock file if autoconfirm is disabled
        if (!$this->autoconfirm) {
            $this->container->save($file['name'] . '.lock', time());
        }
        return $file;
    }
    
    protected function validateFile($file) {
        if (!$this->validator->validate($file)) {
            $file['messages'] = $this->validator->getMessages();
        }
        return $file;
    }
    
    /**
     * Fixes the $_FILES array problem and ensures the result is an array of files
     * 
     * PHP's $_FILES variable is not properly formated for iteration when
     * multiple files are uploaded under the same name
     * @see http://www.php.net/manual/en/features.file-upload.php
     * 
     * @param array $files
     * @return array
     */
    protected function normalizeFiles(array $files) {
        // we have a single file
        if (isset($files['name']) && !is_array($files['name'])) {
            return array($files);
        }
        
        // we have list of files, which PHP messes up
        if (isset($files['name']) && is_array($files['name'])) {
            $result = array();
            foreach ($files['name'] as $k => $v) {
                $result[$k] = array(
                    'name' => $files['name'][$k],
                    'type' => @$files['type'][$k],
                    'size' => @$files['size'][$k],
                    'error' => @$files['error'][$k],
                    'tmp_name' => $files['tmp_name'][$k]
                );
            }
            return $result;
        }
        
        // we have a list of files which are in correct format
        if (isset($files[0]) && isset($files[0]['name'])) {
            return $files;
        }
        
        // if we got here, the $file argument is wrong
        return array();
    }
    
    /**
     * Sanitize the name of the uploaded file by stripping away bad characters
     * and replacing "invalid" characters with underscore _
     * 
     * @param string $name
     * @return string
     */
    protected function sanitizeFileName($name) {
        $name = preg_replace('/[^a-z0-9\.]+/', '_', strtolower($name));
        return preg_replace('/[_]+/', '_', $name);
    }
}