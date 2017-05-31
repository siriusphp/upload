<?php

namespace Sirius\Upload\Util;

class Arr extends \Sirius\Validation\Util\Arr
{
    public static function remapFilesArray(array $files)
    {
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
        // The caller passed $_FILES['some_field_name']
        if (isset($files['name'])) {
            // we have a single file
            if(!is_array($files['name'])) {
                return array($files);
            }
            // we have list of files, which PHP messes up
            else {
                return Arr::remapFilesArray($files);
            }
        }
        // The caller passed $_FILES
        else {
            $keys = array_keys($files);
            if (isset($keys[0]) && isset($files[$keys[0]]['name'])) {
                if (!is_array($files[$keys[0]]['name'])) {
                    // $files is in the correct format already, even in the
                    // case it contains a single element.
                    return $files;
                }
                // we have list of files, which PHP messes up
                else {
                    return Arr::remapFilesArray($files[$keys[0]]);
                }
            }
        }

        // if we got here, the $file argument is wrong
        return array();
    }

}
