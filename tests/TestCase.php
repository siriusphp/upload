<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    function createTemporaryFile($name, $content = "")
    {
        file_put_contents($this->tmpFolder . '/' . $name, $content);
    }
}
