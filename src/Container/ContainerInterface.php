<?php

namespace Sirius\Upload\Container;

interface ContainerInterface
{

    /**
     * Check if the container is writable
     */
    public function isWritable();

    /**
     * This will check if a file is in the container
     *
     * @param string $file
     */
    public function has($file);

    /**
     * Saves the $content string as a file
     *
     * @param string $file
     * @param string $content
     */
    public function save($file, $content);

    /**
     * Delete the file from the container
     *
     * @param string $file
     */
    public function delete($file);

    /**
     * Moves a temporary uploaded file to a destination in the container
     *
     * @param string $localFile   local path
     * @param string $destination
     */
    public function moveUploadedFile($localFile, $destination);
}
