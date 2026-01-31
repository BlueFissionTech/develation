<?php

namespace BlueFission\Tests\HTML;

use PHPUnit\Framework\TestCase;
use BlueFission\HTML\XML;
use BlueFission\Arr;

class XMLTest extends TestCase
{
    public static $testdirectory = 'develation_xml_testdirectory';
    public static $file = 'test.xml';

    public static $classname = 'BlueFission\HTML\XML';

    protected $object;

    private function baseDir(): string
    {
        $baseDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.static::$testdirectory;
        if (!is_dir($baseDir)) {
            @mkdir($baseDir, 0777, true);
        }

        return $baseDir;
    }

    public function setUp(): void
    {
        $baseDir = $this->baseDir();
        $filePath = $baseDir.DIRECTORY_SEPARATOR.static::$file;
        touch($filePath);

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

        file_put_contents($filePath, $data);

        $this->object = new static::$classname();
    }

    public function tearDown(): void
    {
        $baseDir = $this->baseDir();
        $filePath = $baseDir.DIRECTORY_SEPARATOR.static::$file;
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        @rmdir($baseDir);
    }

    public function testParseXML()
    {
        $baseDir = $this->baseDir();
        $this->object = new static::$classname($baseDir.DIRECTORY_SEPARATOR.static::$file);

        $this->assertEquals($baseDir.DIRECTORY_SEPARATOR.static::$file, $this->object->file());
        $this->assertEquals(XML::STATUS_SUCCESS, $this->object->status());
        $this->assertEquals(1, Arr::size($this->object->data()));
        $this->assertEquals('The Great Gatsby', $this->object->data()[0]['child'][0]['child'][0]['content']);
    }

    public function testFileMethod()
    {
        $baseDir = $this->baseDir();
        $this->object->file($baseDir.DIRECTORY_SEPARATOR.static::$file);
        $this->assertEquals($baseDir.DIRECTORY_SEPARATOR.static::$file, $this->object->file());
    }
}
