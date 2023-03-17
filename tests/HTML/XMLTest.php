<?php
namespace BlueFission\Tests\HTML;

use PHPUnit\Framework\TestCase;
use BlueFission\HTML\XML;

class XMLTest extends TestCase
{
    public function testParseXML()
    {
        $file = 'test.xml';
        $xml = new XML($file);
        $this->assertEquals($file, $xml->file());
        $this->assertTrue($xml->parseXML());
    }

    public function testFileMethod()
    {
        $file = 'test.xml';
        $xml = new XML();
        $xml->file($file);
        $this->assertEquals($file, $xml->file());
    }
}
