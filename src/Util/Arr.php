<?php

namespace Sirius\Upload\Util;

class Arr extends \Sirius\Validation\Util\Arr
{

    /**
     * Fixes the $_FILES array problem and ensures the result is an array of files
     *
     * PHP's $_FILES variable is not properly formated for iteration when
     * multiple files are uploaded under the same name
     * @see http://www.php.net/manual/en/features.file-upload.php
     *
     * @param  array $files
     * @return array
     */
    public static function normalizeFiles(array $files)
    {
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

}
