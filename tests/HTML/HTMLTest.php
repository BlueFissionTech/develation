<?php
namespace BlueFission\Tests\HTML;

use BlueFission\HTML\HTML;

class HTMLTest extends \PHPUnit\Framework\TestCase
{
    public function testHrefMethod()
    {
        $_SERVER['DOCUMENT_ROOT'] = 'http://localhost';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/test';
        
        $expected = 'http://localhost/test';
        $result = HTML::href();
        $this->assertEquals($expected, $result);

        $expected = 'https://localhost/test';
        $result = HTML::href(null, true);
        $this->assertEquals($expected, $result);

        $expected = '/test';
        $result = HTML::href(null, false, false);
        $this->assertEquals($expected, $result);
    }

    public function testFormatMethod()
    {
        $expected = '<ol>' . "\n" . '<li>Test content</li>' . "\n" . '</ol>' . "\n";
        $result = HTML::format("~ Test content\n");
        $this->assertEquals($expected, $result);

        $expected = '<b>Test content</b>';
        $result = HTML::format("[b]Test content[/b]", true);
        $this->assertEquals($expected, $result);
    }
}
