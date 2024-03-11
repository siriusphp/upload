<?php
declare(strict_types=1);

namespace Sirius\Upload\Container;

class Local implements ContainerInterface
{
    protected string $baseDirectory;

    public function __construct(string $baseDirectory)
    {
        $this->baseDirectory = $this->normalizePath($baseDirectory) . DIRECTORY_SEPARATOR;
        $this->ensureDirectory($this->baseDirectory);
    }

    protected function normalizePath(string $path): string
    {
        $path = dirname(rtrim($path, '\\/') . DIRECTORY_SEPARATOR . 'xxx');

        return rtrim($path, DIRECTORY_SEPARATOR);
    }

    protected function ensureDirectory(string $directory): bool
    {
        if ( ! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return is_dir($directory) && $this->isWritable();
    }

    /**
     * Check if the container is writable
     */
    public function isWritable(): bool
    {
        return is_writable($this->baseDirectory);
    }

    /**
     * This will check if a file is in the container
     *
     * @param string $file
     *
     * @return bool
     */
    public function has(string $file): bool
    {
        return $file && file_exists($this->baseDirectory . $file);
    }

    public function save(string $file, string $content): bool
    {
        $file = $this->normalizePath($file);
        $dir  = dirname($this->baseDirectory . $file);
        if ($this->ensureDirectory($dir)) {
            return (bool)file_put_contents($this->baseDirectory . $file, $content);
        }

        return false;
    }

    public function delete(string $file): bool
    {
        $file = $this->normalizePath($file);
        if (file_exists($this->baseDirectory . $file)) {
            return unlink($this->baseDirectory . $file);
        }

        return true;
    }

    public function moveUploadedFile(string $localFile, string $destination): bool
    {
        $dir = dirname($this->baseDirectory . $destination);
        if (file_exists($localFile) && $this->ensureDirectory($dir)) {
            if (is_readable($localFile)) {
                // rename() would be good but this is better because $localFile may become 'unwritable'
                $result = copy($localFile, $this->baseDirectory . $destination);
                @unlink($localFile);

                return $result;
            }
        }

        return false;
    }
}
