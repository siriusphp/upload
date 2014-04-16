<?php
/**
 * Created by PhpStorm.
 * User: Florin
 * Date: 4/16/2014
 * Time: 8:19 PM
 */

namespace Sirius\Upload;


class HandlerAggregateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HandlerAggregate
     */
    protected $agg;

    function setUp()
    {
        $this->tmpFolder = realpath(__DIR__ . '/../fixitures/');
        @mkdir($this->tmpFolder . '/container');
        $this->uploadFolder = realpath(__DIR__ . '/../fixitures/container/');

        $this->agg = new HandlerAggregate();
        $this->agg->addHandler(
            'user_picture',
            new Handler(
                $this->uploadFolder . '/user_picture', null, array(
                    Handler::OPTION_PREFIX => '',
                    Handler::OPTION_OVERWRITE => false,
                    Handler::OPTION_AUTOCONFIRM => false
                )
            )
        );
        $this->agg->addHandler(
            'resume',
            new Handler(
                $this->uploadFolder . '/resume', null, array(
                    Handler::OPTION_PREFIX => '',
                    Handler::OPTION_OVERWRITE => false,
                    Handler::OPTION_AUTOCONFIRM => false
                )
            )
        );
        $this->agg->addHandler(
            'portfolio[photos]',
            new Handler(
                $this->uploadFolder . '/photo', null, array(
                    Handler::OPTION_PREFIX => '',
                    Handler::OPTION_OVERWRITE => false,
                    Handler::OPTION_AUTOCONFIRM => false
                )
            )
        );
    }

    function tearDown()
    {
        $files = glob($this->uploadFolder . '/*'); // get all file names
        foreach ($files as $file) { // iterate files
            if (is_file($file)) {
                unlink($file);
            } // delete file
        }
    }

    function createTemporaryFile($name, $content = "")
    {
        file_put_contents($this->tmpFolder . '/' . $name, $content);
    }

    function testProcess() {
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

        $this->assertTrue(file_exists($this->uploadFolder . '/user_picture/' . $result['user_picture']->name));
        $this->assertTrue(file_exists($this->uploadFolder . '/user_picture/' . $result['user_picture']->name . '.lock'));

        $result->confirm();
        $this->assertFalse(file_exists($this->uploadFolder . '/user_picture/' . $result['user_picture']->name . '.lock'));
    }

    function testIterator() {
        $handlers = $this->agg->getIterator();
        $this->assertTrue($handlers['user_picture'] instanceof Handler);
    }
}
