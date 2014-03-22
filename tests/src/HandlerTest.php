<?php

namespace Sirius\Upload;

class HandlerTest extends \PHPUnit_Framework_TestCase {
	
    function setUp() {
        $this->tmpFolder = realpath(__DIR__ . '/../fixitures/');
        $this->uploadFolder = realpath(__DIR__ . '/../fixitures/container/');
        $this->handler = new Handler($this->uploadFolder, null, array(
        	Handler::OPTION_PREFIX => '',
            Handler::OPTION_OVERWRITE => false,
            Handler::OPTION_AUTOCONFIRM => false
        ));
    }
    
    function tearDown() {
        $files = glob($this->uploadFolder . '/*'); // get all file names
        foreach($files as $file){ // iterate files
            if(is_file($file))
                unlink($file); // delete file
        }
    }

    function createTemporaryFile($name, $content = "") {
        file_put_contents($this->tmpFolder. '/' . $name, $content);
    }
    
    function testBasicUploadWithPrefix() {
        $this->handler->setPrefix('subfolder/');
        $this->createTemporaryFile('abc.tmp');

        $result = $this->handler->process(array(
        	'name' => 'abc.jpg',
            'tmp_name' => $this->tmpFolder . '/abc.tmp'
        ));

        $this->assertTrue(file_exists($this->uploadFolder . '/' . $result->name));
        $this->assertTrue(file_exists($this->uploadFolder . '/' . $result->name . '.lock'));
        // tearDown does not clean the subfolders
        unlink($this->uploadFolder . '/' . $result->name);
        unlink($this->uploadFolder . '/' . $result->name . '.lock');
    }

    function testUploadOverwrite() {
        $this->createTemporaryFile('abc.tmp', 'first_file');
    
        $result = $this->handler->process(array(
            'name' => 'abc.jpg',
            'tmp_name' => $this->tmpFolder . '/abc.tmp'
        ));
    
        $this->assertEquals(file_get_contents($this->uploadFolder . '/abc.jpg'), 'first_file');

        // no overwrite, the first upload should be preserved
        $this->handler->setOverwrite(false);
        $this->createTemporaryFile('abc.tmp', 'second_file');
    
        $result = $this->handler->process(array(
            'name' => 'abc.jpg',
            'tmp_name' => $this->tmpFolder . '/abc.tmp'
        ));
    
        $this->assertEquals(file_get_contents($this->uploadFolder . '/abc.jpg'), 'first_file');

        // overwrite, the first uploaded file should be changed
        $this->handler->setOverwrite(true);
        $this->createTemporaryFile('abc.tmp', 'second_file');
    
        $result = $this->handler->process(array(
            'name' => 'abc.jpg',
            'tmp_name' => $this->tmpFolder . '/abc.tmp'
        ));
    
        $this->assertEquals(file_get_contents($this->uploadFolder . '/abc.jpg'), 'second_file');
    }
    
    function testUploadAutoconfirm() {
        $this->handler->setAutoconfirm(true);
        $this->createTemporaryFile('abc.tmp', 'first_file');
        
        $result = $this->handler->process(array(
            'name' => 'abc.jpg',
            'tmp_name' => $this->tmpFolder . '/abc.tmp'
        ));
        
        $this->assertTrue(file_exists($this->uploadFolder . '/' . $result->name));
        $this->assertFalse(file_exists($this->uploadFolder . '/' . $result->name . '.lock'));
    }
    
    function testSingleUploadConfirmation() {
        $this->createTemporaryFile('abc.tmp', 'first_file');
        
        $result = $this->handler->process(array(
            'name' => 'abc.jpg',
            'tmp_name' => $this->tmpFolder . '/abc.tmp'
        ));
        
        $this->assertTrue(file_exists($this->uploadFolder . '/' . $result->name));
        $this->assertTrue(file_exists($this->uploadFolder . '/' . $result->name . '.lock'));
        
        $this->handler->confirm($result);
        $this->assertFalse(file_exists($this->uploadFolder . '/' . $result->name . '.lock'));
    }


    function testSingleUploadClearing() {
        $this->createTemporaryFile('abc.tmp', 'first_file');
        
        $result = $this->handler->process(array(
            'name' => 'abc.jpg',
            'tmp_name' => $this->tmpFolder . '/abc.tmp'
        ));
        
        $this->assertTrue(file_exists($this->uploadFolder . '/' . $result->name));
        $this->assertTrue(file_exists($this->uploadFolder . '/' . $result->name . '.lock'));
        
        $this->handler->clear($result);
        $this->assertFalse(file_exists($this->uploadFolder . '/' . $result->name));
        $this->assertFalse(file_exists($this->uploadFolder . '/' . $result->name . '.lock'));
    }
    
    function testMultiUpload() {
        $this->createTemporaryFile('abc.tmp', 'first_file');
        $this->createTemporaryFile('def.tmp', 'first_file');
        
        // array is already properly formated
        $result = $this->handler->process(array(
            array(
                'name' => 'abc.jpg',
                'tmp_name' => $this->tmpFolder . '/abc.tmp'
            ),
            array(
                'name' => 'def.jpg',
                'tmp_name' => $this->tmpFolder . '/def.tmp'
            )
        ));
        
#        var_dump(glob($this->uploadFolder . '/*'));
        foreach ($result as $file) {
            $this->assertTrue(file_exists($this->uploadFolder . '/' . $file->name));
            $this->assertTrue(file_exists($this->uploadFolder . '/' . $file->name . '.lock'));
        }
        
        // confirmation removes the .lock files
        $this->handler->confirm($result);
        foreach ($result as $file) {
            $this->assertTrue(file_exists($this->uploadFolder . '/' . $file->name));
            $this->assertFalse(file_exists($this->uploadFolder . '/' . $file->name . '.lock'));
        }

        // clearing removes the uploaded files and their locks (which are already removed)
        $this->handler->clear($result);
        foreach ($result as $file) {
            $this->assertFalse(file_exists($this->uploadFolder . '/' . $file->name));
        }
    }
    
    function testOriginalMultiUpload() {
        $this->createTemporaryFile('abc.tmp', 'first_file');
        $this->createTemporaryFile('def.tmp', 'first_file');
        
        // array is as provided by PHP
        $result = $this->handler->process(array(
            'name' => array(
                'abc.jpg',
                'def.jpg',	
            ),
            'tmp_name' => array(
                $this->tmpFolder . '/abc.tmp',
                $this->tmpFolder . '/def.tmp'
            ),
        ));

        $this->assertEquals(count($result), 2);
        foreach ($result as $file) {
            $this->assertTrue(file_exists($this->uploadFolder . '/' . $file->name));
            $this->assertTrue(file_exists($this->uploadFolder . '/' . $file->name . '.lock'));
        }
    }
    
    function testWrongFilesArray() {
        $result = $this->handler->process(array('names' => 'abc.jpg'));
        $this->assertEquals(count($result), 0);
    }
}