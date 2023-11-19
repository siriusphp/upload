<?php
declare(strict_types=1);

namespace Sirius\Upload\Container;

interface ContainerInterface
{

    /**
     * Check if the container is writable
     */
    public function isWritable(): bool;

    /**
     * This will check if a file is in the container
     */
    public function has(string $file): bool;

    /**
     * Saves the $content string as a file
     *
     * @param string $file
     * @param string $content
     */
    public function save(string $file, string $content): bool;

    /**
     * Delete the file from the container
     */
    public function delete(string $file): bool;

    /**
     * Moves a temporary uploaded file to a destination in the container
     */
    public function moveUploadedFile(string $localFile, string $destination): bool;
}
