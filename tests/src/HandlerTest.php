<?php

use Laminas\Diactoros\StreamFactory;
use \Sirius\Upload\Handler;
use Laminas\Diactoros\UploadedFile;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;


beforeEach(function () {
    $this->tmpFolder = realpath(__DIR__ . '/../fixitures/');
    if (!is_dir($this->tmpFolder)) {
        @mkdir($this->tmpFolder . '/container');
    }
    $this->uploadFolder = realpath(__DIR__ . '/../fixitures/container/');
    $this->handler      = new Handler(
        $this->uploadFolder, array(
            Handler::OPTION_PREFIX      => '',
            Handler::OPTION_OVERWRITE   => false,
            Handler::OPTION_AUTOCONFIRM => false
        )
    );
});

afterEach(function () {
    $files = glob($this->uploadFolder . '/*');
    // get all file names
    foreach ($files as $file) { // iterate files
        if (is_file($file)) {
            unlink($file);
        } // delete file
    }
});

test('basic upload with prefix', function () {
    $this->handler->setPrefix('subfolder/');
    $this->createTemporaryFile('abc.tmp');

    $result = $this->handler->process(
        array(
            'name'     => 'abc.jpg',
            'tmp_name' => $this->tmpFolder . '/abc.tmp'
        )
    );

    expect(file_exists($this->uploadFolder . '/' . $result->name))->toBeTrue();
    expect(file_exists($this->uploadFolder . '/' . $result->name . '.lock'))->toBeTrue();

    // tearDown does not clean the subfolders
    unlink($this->uploadFolder . '/' . $result->name);
    unlink($this->uploadFolder . '/' . $result->name . '.lock');
});

test('upload overwrite', function () {
    $this->createTemporaryFile('abc.tmp', 'first_file');

    $result = $this->handler->process(
        array(
            'name'     => 'abc.jpg',
            'tmp_name' => $this->tmpFolder . '/abc.tmp'
        )
    );

    expect('first_file')->toEqual(file_get_contents($this->uploadFolder . '/abc.jpg'));

    // no overwrite, the first upload should be preserved
    $this->handler->setOverwrite(false);
    $this->createTemporaryFile('abc.tmp', 'second_file');

    $result = $this->handler->process(
        array(
            'name'     => 'abc.jpg',
            'tmp_name' => $this->tmpFolder . '/abc.tmp'
        )
    );

    expect('first_file')->toEqual(file_get_contents($this->uploadFolder . '/abc.jpg'));

    // overwrite, the first uploaded file should be changed
    $this->handler->setOverwrite(true);
    $this->createTemporaryFile('abc.tmp', 'second_file');

    $result = $this->handler->process(
        array(
            'name'     => 'abc.jpg',
            'tmp_name' => $this->tmpFolder . '/abc.tmp'
        )
    );

    expect('second_file')->toEqual(file_get_contents($this->uploadFolder . '/abc.jpg'));
});

test('upload autoconfirm', function () {
    $this->handler->setAutoconfirm(true);
    $this->createTemporaryFile('abc.tmp', 'first_file');

    $result = $this->handler->process(
        array(
            'name'     => 'abc.jpg',
            'tmp_name' => $this->tmpFolder . '/abc.tmp'
        )
    );

    expect(file_exists($this->uploadFolder . '/' . $result->name))->toBeTrue();
    expect(file_exists($this->uploadFolder . '/' . $result->name . '.lock'))->toBeFalse();
});

test('single upload confirmation', function () {
    $this->createTemporaryFile('abc.tmp', 'first_file');

    $result = $this->handler->process(
        array(
            'name'     => 'abc.jpg',
            'tmp_name' => $this->tmpFolder . '/abc.tmp'
        )
    );

    expect(file_exists($this->uploadFolder . '/' . $result->name))->toBeTrue();
    expect(file_exists($this->uploadFolder . '/' . $result->name . '.lock'))->toBeTrue();

    $result->confirm();
    expect(file_exists($this->uploadFolder . '/' . $result->name . '.lock'))->toBeFalse();
});

test('single upload clearing', function () {
    $this->createTemporaryFile('abc.tmp', 'first_file');

    $result = $this->handler->process(
        array(
            'name'     => 'abc.jpg',
            'tmp_name' => $this->tmpFolder . '/abc.tmp'
        )
    );

    expect(file_exists($this->uploadFolder . '/' . $result->name))->toBeTrue();
    expect(file_exists($this->uploadFolder . '/' . $result->name . '.lock'))->toBeTrue();

    $fileName = $result->name;
    $result->clear();

    expect(file_exists($this->uploadFolder . '/' . $fileName))->toBeFalse();
    expect(file_exists($this->uploadFolder . '/' . $fileName . '.lock'))->toBeFalse();
});

test('multi upload', function () {
    $this->createTemporaryFile('abc.tmp', 'first_file');
    $this->createTemporaryFile('def.tmp', 'first_file');

    // array is already properly formated
    $result = $this->handler->process(
        array(
            array(
                'name'     => 'abc.jpg',
                'tmp_name' => $this->tmpFolder . '/abc.tmp'
            ),
            array(
                'name'     => 'def.jpg',
                'tmp_name' => $this->tmpFolder . '/def.tmp'
            )
        )
    );

    expect($result->isValid())->toBeTrue();

    #        var_dump(glob($this->uploadFolder . '/*'));
    foreach ($result as $file) {
        expect(file_exists($this->uploadFolder . '/' . $file->name))->toBeTrue();
        expect(file_exists($this->uploadFolder . '/' . $file->name . '.lock'))->toBeTrue();
    }

    // confirmation removes the .lock files
    $result->confirm();
    foreach ($result as $file) {
        expect(file_exists($this->uploadFolder . '/' . $file->name))->toBeTrue();
        expect(file_exists($this->uploadFolder . '/' . $file->name . '.lock'))->toBeFalse();
    }

    // clearing removes the uploaded files and their locks (which are already removed)
    $result->clear();
    foreach ($result as $file) {
        expect($file->name)->toBeNull();
    }
});

test('original multi upload', function () {
    $this->createTemporaryFile('abc.tmp', 'first_file');
    $this->createTemporaryFile('def.tmp', 'first_file');

    // array is as provided by PHP
    $result = $this->handler->process(
        array(
            'name'     => array(
                'abc.jpg',
                'def.jpg',
            ),
            'tmp_name' => array(
                $this->tmpFolder . '/abc.tmp',
                $this->tmpFolder . '/def.tmp'
            ),
        )
    );

    expect(2)->toEqual(count($result));
    foreach ($result as $file) {
        expect(file_exists($this->uploadFolder . '/' . $file->name))->toBeTrue();
        expect(file_exists($this->uploadFolder . '/' . $file->name . '.lock'))->toBeTrue();
    }
});

test('wrong files array', function () {
    $result = $this->handler->process(array('names' => 'abc.jpg'));
    expect(0)->toEqual(count($result));
});

test('exception trwon for invalid container', function () {
    $this->expectException('Sirius\Upload\Exception\InvalidContainerException');

    $handler = new Handler(new \stdClass());
});

test('single upload validation', function () {
    $this->createTemporaryFile('abc.tmp', 'non image file');

    // uploaded files must be an image
    $this->handler->addRule(Handler::RULE_IMAGE);

    $result = $this->handler->process(
        array(
            'name'     => 'abc.jpg',
            'tmp_name' => $this->tmpFolder . '/abc.tmp'
        )
    );

    expect($result->isValid())->toBeFalse();
    expect(1)->toEqual(count($result->getMessages()));
    expect($result->nonAttribute)->toBeNull();
});

test('multi upload validation', function () {

    $this->createTemporaryFile('abc.tmp', 'first_file');
    $this->createTemporaryFile('def.tmp', 'second_file');

    // uploaded file must be an image
    $this->handler->addRule(Handler::RULE_IMAGE);

    // array is as provided by PHP
    $result   = $this->handler->process(
        array(
            'name'     => array(
                'abc.jpg',
                'def.jpg',
            ),
            'tmp_name' => array(
                $this->tmpFolder . '/abc.tmp',
                $this->tmpFolder . '/def.tmp'
            ),
        )
    );
    $messages = $result->getMessages();

    expect($result->isValid())->toBeFalse();
    expect(2)->toEqual(count($messages));
    expect(1)->toEqual(count($messages[0]));
});

test('custom sanitization callback', function () {
    $this->handler->setSanitizerCallback(function ($name) {
        return preg_replace('/[^A-Za-z0-9\.]+/', '-', strtolower($name));
    });
    $this->createTemporaryFile('ABC 123.tmp', 'non image file');

    $result = $this->handler->process(
        array(
            'name'     => 'ABC 123.tmp',
            'tmp_name' => $this->tmpFolder . '/ABC 123.tmp'
        )
    );

    expect(file_exists($this->uploadFolder . '/abc-123.tmp'))->toBeTrue();
});

test('psr7 uploaded files', function () {
    $files = ['abc.tmp', 'def.tmp'];

    $psr7Files = [];

    foreach ($files as $file) {
        $this->createTemporaryFile($file, 'first_file');

        $factory     = new StreamFactory();
        $stream      = $factory->createStreamFromFile($this->tmpFolder . '/' . $file);
        $psr7Files[] = new UploadedFile(
            $stream,
            $stream->getSize(),
            UPLOAD_ERR_OK,
            $file
        );
    }


    $result = $this->handler->process($psr7Files);

    foreach ($result as $item) {
        expect(file_exists($this->uploadFolder . '/' . $item->name))->toBeTrue();
        expect(file_exists($this->uploadFolder . '/' . $item->name . '.lock'))->toBeTrue();

        $item->confirm();
        expect(file_exists($this->uploadFolder . '/' . $item->name . '.lock'))->toBeFalse();
    }
});

test('single psr7 uploaded file', function () {
    $file = 'abc.tmp';

    $this->createTemporaryFile($file, 'first_file');

    $factory  = new StreamFactory();
    $stream   = $factory->createStreamFromFile($this->tmpFolder . '/' . $file);
    $psr7File = new UploadedFile(
        $stream,
        $stream->getSize(),
        UPLOAD_ERR_OK,
        $file
    );

    $result = $this->handler->process($psr7File);

    expect(file_exists($this->uploadFolder . '/' . $result->name))->toBeTrue();
    expect(file_exists($this->uploadFolder . '/' . $result->name . '.lock'))->toBeTrue();

    $result->confirm();
    expect(file_exists($this->uploadFolder . '/' . $result->name . '.lock'))->toBeFalse();
});

test('symfony uploaded files', function () {
    $files = ['abc.tmp', 'def.tmp'];

    $symfonyFiles = [];

    foreach ($files as $file) {
        $this->createTemporaryFile($file, 'first_file');

        $symfonyFiles[] = new SymfonyUploadedFile($this->tmpFolder . '/' . $file, $file);
    }

    $result = $this->handler->process($symfonyFiles);

    foreach ($result as $item) {
        expect(file_exists($this->uploadFolder . '/' . $item->name))->toBeTrue();
        expect(file_exists($this->uploadFolder . '/' . $item->name . '.lock'))->toBeTrue();

        $item->confirm();
        expect(file_exists($this->uploadFolder . '/' . $item->name . '.lock'))->toBeFalse();
    }
});

test('single symfony uploaded file', function () {
    $file = 'abc.tmp';

    $this->createTemporaryFile($file, 'first_file');

    $symfonyFile = new SymfonyUploadedFile($this->tmpFolder . '/' . $file, $file);

    $result = $this->handler->process($symfonyFile);

    expect(file_exists($this->uploadFolder . '/' . $result->name))->toBeTrue();
    expect(file_exists($this->uploadFolder . '/' . $result->name . '.lock'))->toBeTrue();

    $result->confirm();
    expect(file_exists($this->uploadFolder . '/' . $result->name . '.lock'))->toBeFalse();
});
