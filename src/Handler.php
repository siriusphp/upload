<?php
namespace Sirius\Upload;

use Sirius\Upload\Container\ContainerInterface;
use Sirius\Upload\Container\Local as LocalContainer;
use Sirius\Upload\Exception\InvalidContainerException;
use Sirius\Upload\Util\Arr;
use Sirius\Validation\ErrorMessage;
use Sirius\Validation\ValueValidator;

class Handler implements UploadHandlerInterface
{
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
     * @var \Sirius\Validation\ValueValidator
     */
    protected $validator;
    
    /**
     * @var function|callback
     */
    protected $sanitizerCallback;

    /**
     * @param $directoryOrContainer
     * @param  array                               $options
     * @param  ValueValidator                      $validator
     * @throws InvalidContainerException
     */
    public function __construct($directoryOrContainer, $options = array(), ValueValidator $validator = null)
    {
        $container = $directoryOrContainer;
        if (is_string($directoryOrContainer)) {
            $container = new LocalContainer($directoryOrContainer);
        }
        if (!$container instanceof ContainerInterface) {
            throw new InvalidContainerException('Destination container for uploaded files is not valid');
        }
        $this->container = $container;

        // create the validator
        if (!$validator) {
            $validator = new ValueValidator();
        }
        $this->validator = $validator;

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
     * @param  bool                   $overwrite
     * @return \Sirius\Upload\Handler
     */
    public function setOverwrite($overwrite)
    {
        $this->overwrite = (bool) $overwrite;

        return $this;
    }

    /**
     * File prefix for the upload. Can be
     * - a folder (if it ends with /)
     * - a string to be used as prefix
     * - a function that returns a string
     *
     * @param  string|callable        $prefix
     * @return \Sirius\Upload\Handler
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Enable/disable upload autoconfirmation
     * Autoconfirmation does not require calling `confirm()`
     *
     * @param  boolean                $autoconfirm
     * @return \Sirius\Upload\Handler
     */
    public function setAutoconfirm($autoconfirm)
    {
        $this->autoconfirm = (bool) $autoconfirm;

        return $this;
    }
    
    /**
     * Set the sanitizer function for cleaning up the file names
     * 
     * @param callable $callback
     * @throws \InvalidArgumentException
     * @return \Sirius\Upload\Handler
     */
    function setSanitizerCallback($callback)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('The $callback parameter is not a valid callable entity');
        }
        $this->sanitizerCallback = $callback;
        return $this;
    }

    /**
     * Add validation rule (extension|size|width|height|ratio)
     *
     * @param  string                 $name
     * @param  mixed                  $options
     * @param  string                 $errorMessageTemplate
     * @param  string                 $label
     * @return \Sirius\Upload\Handler
     */
    public function addRule($name, $options = null, $errorMessageTemplate = null, $label = null)
    {
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

    /**
     * Processes a file upload and returns an upload result file/collection
     *
     * @param  array                         $files
     * @return Result\Collection|Result\File
     */
    public function process($files = array())
    {
        $files = Arr::normalizeFiles($files);

        foreach ($files as $k => $file) {
            $files[$k] = $this->processSingleFile($file);
        }

        if (count($files) == 1) {
            return new Result\File(array_pop($files), $this->container);
        }

        return new Result\Collection($files, $this->container);
    }

    /**
     * Processes a single uploaded file
     * - sanitize the name
     * - validates the file
     * - if valid, moves the file to the container
     *
     * @param  array $file
     * @return array
     */
    protected function processSingleFile(array $file)
    {
        // store it for future reference
        $file['original_name'] = $file['name'];

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

    /**
     * Validates a file according to the rules configured on the handler
     *
     * @param $file
     * @return mixed
     */
    protected function validateFile($file)
    {
        if (!$this->validator->validate($file)) {
            $file['messages'] = $this->validator->getMessages();
        }

        return $file;
    }

    /**
     * Sanitize the name of the uploaded file by stripping away bad characters
     * and replacing "invalid" characters with underscore _
     *
     * @param  string $name
     * @return string
     */
    protected function sanitizeFileName($name)
    {
        if ($this->sanitizerCallback) {
            return call_user_func($this->sanitizerCallback, $name);
        }
        return preg_replace('/[^A-Za-z0-9\.]+/', '_', $name);
    }
}
