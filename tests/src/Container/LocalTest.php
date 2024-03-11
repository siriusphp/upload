<?php

use Sirius\Upload\Container\Local as LocalContainer;


function rrmdir($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (filetype($dir . "/" . $object) == "dir") {
                    rrmdir($dir . "/" . $object);
                } else {
                    unlink($dir . "/" . $object);
                }
            }
        }
        reset($objects);
        rmdir($dir);
    }
}

beforeEach(function () {
    $this->dir = realpath(__DIR__ . '/../../') . '/fixture/';
    $this->container = new LocalContainer($this->dir);
});

afterEach(function () {
    rrmdir($this->dir);
});

test('save', function () {
    $file = 'subdir/test.txt';
    expect($this->container->save($file, 'cool'))->toBeTrue();
    expect(file_exists($this->dir . $file))->toBeTrue();
    expect($this->container->has($file))->toBeTrue();
    expect(file_get_contents($this->dir . $file))->toEqual('cool');
});

test('delete', function () {
    $file = 'subdir/test.txt';
    $this->container->save($file, 'cool');
    expect(file_exists($this->dir . $file))->toBeTrue();
    expect($this->container->delete($file))->toBeTrue();
    expect(file_exists($this->dir . $file))->toBeFalse();
});

test('delete inexisting file', function () {
    $file = 'subdir/test.txt';
    expect($this->container->delete($file))->toBeTrue();
});

test('move uploaded file', function () {
    $file = 'test.txt';
    $file2 = 'sub/test.txt';
    $this->container->save($file, 'cool');
    expect($this->container->moveUploadedFile($this->dir . $file, $file2))->toBeTrue();
    expect(file_get_contents($this->dir . $file2))->toEqual('cool');
});

test('move missing uploaded file', function () {
    $file = 'subdir/test.txt';
    expect($this->container->moveUploadedFile($this->dir . $file, $file))->toBeFalse();
});
