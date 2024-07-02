<?php
namespace BlueFission\Tests\HTML;

use BlueFission\HTML\HTML;

class HTMLTest extends \PHPUnit\Framework\TestCase
{
    public function testHrefMethod()
    {   
        $expected = 'http://localhost';
        $result = HTML::href();
        $this->assertEquals($expected, $result);

        $expected = '';
        $result = HTML::href(null, false);
        $this->assertEquals($expected, $result);
    }

    public function testFormatMethod()
    {
        $expected = '<ol><li>Test content</li>' . "\n" . '</ol>';
        $result = HTML::format("- Test content\n");
        $this->assertEquals($expected, $result);

        $expected = '<strong>Test content</strong>';
        $result = HTML::format("**Test content**", true);
        $this->assertEquals($expected, $result);

        $expected = '<em>Test content</em>';
        $result = HTML::format("*Test content*", true);
        $this->assertEquals($expected, $result);
        $this->assertEquals($expected, $result);

        $expected = '<u>Test content</u>';
        $result = HTML::format("_Test content_", true);
        $this->assertEquals($expected, $result);
    }
}
