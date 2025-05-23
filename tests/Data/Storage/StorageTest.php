<?php

namespace BlueFission\Tests\Data\Storage;

use BlueFission\Data\Storage\Storage;
use BlueFission\Behavioral\Behaviors\Event;

class StorageTest extends \PHPUnit\Framework\TestCase
{
    public static $testdirectory = '../../testdirectory';

    public static $classname = 'BlueFission\Data\Storage\Storage';

    public static $configuration = [];

    protected $object;

    public function setUp(): void
    {
        $this->object = new static::$classname(static::$configuration);
    }

    public function testStorageCanActivate()
    {
        $value = false;
        $this->object->when(Event::ACTIVATED, function ($b, $args) use (&$value) {
            $value = true;
        })->activate();

        $this->assertTrue($value);
    }

    public function testStorageCanRead()
    {
        $this->object->activate();

        $this->object->read();
    }

    public function testStorageCanWriteFields()
    {
        $this->object->activate();

        $this->object->var1 = 'checking';
        $this->object->var2 = 'confirming';
        $this->object->write();
    }

    public function testStorageCanWriteContentOverFields()
    {
        $this->object->activate();
        $this->object->var1 = 'checking';
        $this->object->var2 = 'confirming';
        $this->object->contents("Testing.");
        $this->object->write();
    }
}
