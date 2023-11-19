<?php

use \Sirius\Upload\Handler;
use \Sirius\Upload\HandlerAggregate;

beforeEach(function () {
    $this->tmpFolder = realpath(__DIR__ . '/../fixitures/');
    if (!is_dir($this->tmpFolder)) {
        @mkdir($this->tmpFolder . '/container');
    }
    $this->uploadFolder = realpath(__DIR__ . '/../fixitures/container/');

    $this->agg = new HandlerAggregate();
    $this->agg->addHandler(
        'user_picture',
        new Handler(
            $this->uploadFolder . '/user_picture', array(
                Handler::OPTION_PREFIX => '',
                Handler::OPTION_OVERWRITE => false,
                Handler::OPTION_AUTOCONFIRM => false
            )
        )
    );
    $this->agg->addHandler(
        'resume',
        new Handler(
            $this->uploadFolder . '/resume', array(
                Handler::OPTION_PREFIX => '',
                Handler::OPTION_OVERWRITE => false,
                Handler::OPTION_AUTOCONFIRM => false
            )
        )
    );
    $this->agg->addHandler(
        'portfolio[photos]',
        new Handler(
            $this->uploadFolder . '/photo', array(
                Handler::OPTION_PREFIX => '',
                Handler::OPTION_OVERWRITE => false,
                Handler::OPTION_AUTOCONFIRM => false
            )
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

test('process', function () {
    $this->createTemporaryFile('abc.tmp');
    $this->createTemporaryFile('def.tmp');
    $files = array(
        'user_picture' => array(
            'name' => 'pic.jpg',
            'tmp_name' => $this->tmpFolder . '/abc.tmp'
        ),
        'resume' => array(
            'name' => 'resume.doc',
            'tmp_name' => $this->tmpFolder . '/def.tmp'
        )
    );
    $result = $this->agg->process($files);

    expect(file_exists($this->uploadFolder . '/user_picture/' . $result['user_picture']->name))->toBeTrue();
    expect(file_exists($this->uploadFolder . '/user_picture/' . $result['user_picture']->name . '.lock'))->toBeTrue();

    $result->confirm();
    expect(file_exists($this->uploadFolder . '/user_picture/' . $result['user_picture']->name . '.lock'))->toBeFalse();
});

test('iterator', function () {
    $handlers = $this->agg->getIterator();
    expect($handlers['user_picture'] instanceof Handler)->toBeTrue();
});
