<?php
namespace BlueFission\Tests\HTML;

use PHPUnit\Framework\TestCase;
use BlueFission\HTML\XML;
use BlueFission\Arr;

class XMLTest extends TestCase
{
    static $testdirectory = '../../testdirectory';
    static $file = 'test.xml';

    static $classname = 'BlueFission\HTML\XML';

    protected $object;

    public function setUp() :void
    {
        chdir(__DIR__);

        touch(static::$testdirectory.DIRECTORY_SEPARATOR.static::$file);

        $data = '<?xml version="1.0" encoding="UTF-8"?>
            <library>
                <book>
                    <title>The Great Gatsby</title>
                    <author>F. Scott Fitzgerald</author>
                    <year>1925</year>
                </book>
                <book>
                    <title>To Kill a Mockingbird</title>
                    <author>Harper Lee</author>
                    <year>1960</year>
                </book>
                <book>
                    <title>1984</title>
                    <author>George Orwell</author>
                    <year>1949</year>
                </book>
            </library>';

        file_put_contents(static::$testdirectory.DIRECTORY_SEPARATOR.static::$file, $data);

        $this->object = new static::$classname();
    }

    public function tearDown(): void
    {
        if (file_exists(static::$testdirectory.DIRECTORY_SEPARATOR.static::$file)) {
            @unlink(static::$testdirectory.DIRECTORY_SEPARATOR.static::$file);
        }
    }

    public function testParseXML()
    {
        $this->object = new static::$classname(static::$testdirectory.DIRECTORY_SEPARATOR.static::$file);

        $this->assertEquals(static::$testdirectory.DIRECTORY_SEPARATOR.static::$file, $this->object->file());
        $this->assertEquals(XML::STATUS_SUCCESS, $this->object->status());
        $this->assertEquals(1, Arr::size($this->object->data()));
        $this->assertEquals('The Great Gatsby', $this->object->data()[0]['child'][0]['child'][0]['content']);
    }

    public function testFileMethod()
    {
        $this->object->file(static::$testdirectory.DIRECTORY_SEPARATOR.static::$file);
        $this->assertEquals(static::$testdirectory.DIRECTORY_SEPARATOR.static::$file, $this->object->file());
    }
}
