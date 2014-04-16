<?php
namespace Sirius\Upload\Container;

use Sirius\Upload\Container\Local as LocalContainer;

// define the function in this namespace so it takes precedence over the native PHP implementation
function is_uploaded_file($file)
{
    return file_exists($file);
}

function move_uploaded_file($source, $destination)
{
    return rename($source, $destination);
}

class LocalTest extends \PHPUnit_Framework_TestCase
{

    function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . "/" . $object) == "dir") {
                        $this->rrmdir($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

    function setUp()
    {
        $this->dir = realpath(__DIR__ . '/../../') . '/fixture/';
        $this->container = new LocalContainer($this->dir);
    }

    function tearDown()
    {
        $this->rrmdir($this->dir);
    }

    function testSave()
    {
        $file = 'subdir/test.txt';
        $this->assertTrue($this->container->save($file, 'cool'));
        $this->assertTrue(file_exists($this->dir . $file));
        $this->assertTrue($this->container->has($file));
        $this->assertEquals('cool', file_get_contents($this->dir . $file));
    }

    function testDelete()
    {
        $file = 'subdir/test.txt';
        $this->container->save($file, 'cool');
        $this->assertTrue(file_exists($this->dir . $file));
        $this->assertTrue($this->container->delete($file));
        $this->assertFalse(file_exists($this->dir . $file));
    }

    function testDeleteInexistingFile()
    {
        $file = 'subdir/test.txt';
        $this->assertTrue($this->container->delete($file));
    }

    function testMoveUploadedFile()
    {
        $file = 'test.txt';
        $file2 = 'sub/test.txt';
        $this->container->save($file, 'cool');
        $this->assertTrue($this->container->moveUploadedFile($this->dir . $file, $file2));
        $this->assertEquals('cool', file_get_contents($this->dir . $file2));
    }

    function testMoveMissingUploadedFile()
    {
        $file = 'subdir/test.txt';
        $this->assertFalse($this->container->moveUploadedFile($this->dir . $file, $file));
    }

}
