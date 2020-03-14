<?php
declare(strict_types=1);

namespace Sirius\Upload\Util;

class Helper
{
    const PSR7_UPLOADED_FILE_CLASS = '\Psr\Http\Message\UploadedFileInterface';
    const SYMFONY_UPLOADED_FILE_CLASS = 'Symfony\Component\HttpFoundation\File\UploadedFile';

    /**
     * We do not type-hint or import the class since it may not be used
     *
     * @param \Psr\Http\Message\UploadedFileInterface $file
     *
     * @return array
     */
    public static function extractFromUploadedFileInterface($file)
    {
        /** @var \Psr\Http\Message\UploadedFileInterface $file */
        $tempName = tempnam(sys_get_temp_dir(), 'srsupld_');
        $file->moveTo($tempName);
        $result = [
            'name'     => $file->getClientFilename(),
            'tmp_name' => $tempName,
            'type'     => $file->getClientMediaType(),
            'error'    => $file->getError(),
            'size'     => $file->getSize()
        ];

        return $result;
    }

    public static function extractFromSymfonyFile($file)
    {
        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */

        $result = [
            'name'     => $file->getClientOriginalName(),
            'tmp_name' => $file->getPathname(),
            'type'     => $file->getMimeType(),
            'error'    => $file->getError(),
            'size'     => $file->getSize()
        ];

        return $result;
    }

    public static function remapFilesArray(array $files): array
    {
        $result = [];
        foreach (array_keys($files['name']) as $k) {
            $result[$k] = [
                'name'     => $files['name'][$k],
                'type'     => @$files['type'][$k],
                'size'     => @$files['size'][$k],
                'error'    => @$files['error'][$k],
                'tmp_name' => $files['tmp_name'][$k]
            ];
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
     * @param array|Symfony\Component\HttpFoundation\File\UploadedFile|\Psr\Http\Message\UploadedFileInterface $files
     *
     * @return array
     */
    public static function normalizeFiles($files): array
    {
        if (empty($files)) {
            return [];
        }

        if (is_object($files)) {
            if (is_subclass_of($files, self::PSR7_UPLOADED_FILE_CLASS)) {
                return [self::extractFromUploadedFileInterface($files)];
            }
            if (get_class($files) == self::SYMFONY_UPLOADED_FILE_CLASS) {
                return [self::extractFromSymfonyFile($files)];
            }
        }

        // If caller passed in an array of objects (Either PSR7 or Symfony)
        if (is_array($files) && is_object(reset($files))) {
            if (is_subclass_of(reset($files), self::PSR7_UPLOADED_FILE_CLASS)) {
                $result = [];
                foreach ($files as $file) {
                    $result[] = self::extractFromUploadedFileInterface($file);
                }

                return $result;
            }

            if (get_class(reset($files)) == self::SYMFONY_UPLOADED_FILE_CLASS) {
                $result = [];
                foreach ($files as $file) {
                    $result[] = self::extractFromSymfonyFile($file);
                }

                return $result;
            }
        }

        // The caller passed $_FILES['some_field_name']
        if (isset($files['name'])) {
            // we have a single file
            if (! is_array($files['name'])) {
                return [$files];
            } else {
                // we have list of files, which PHP messes up
                return Helper::remapFilesArray($files);
            }
        } else {
            // The caller passed $_FILES
            $keys = array_keys($files);
            if (isset($keys[0]) && isset($files[$keys[0]]['name'])) {
                if (! is_array($files[$keys[0]]['name'])) {
                    // $files is in the correct format already, even in the
                    // case it contains a single element.
                    return $files;
                } else {
                    // we have list of files, which PHP messes up
                    return Helper::remapFilesArray($files[$keys[0]]);
                }
            }
        }

        // If we got here, the $file argument is wrong
        return [];
    }
}
