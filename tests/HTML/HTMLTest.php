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

        $expected = '/';
        $result = HTML::href('/');
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

    public function testPaginateUsesHttpQueryHelper()
    {
        $originalGet = $_GET ?? [];
        $originalPost = $_POST ?? [];

        $_GET = ['start' => 0, 'lim' => 10, 'filter' => 'active'];
        $_POST = ['token' => 'abc 123'];

        $result = HTML::paginate(30, 'start', 'lim', '/items', 10);

        $this->assertStringContainsString('token=abc+123', $result);
        $this->assertStringContainsString('filter=active', $result);

        $_GET = $originalGet;
        $_POST = $originalPost;
    }

    public function testResultsUsesNativePaginationHelper()
    {
        $originalGet = $_GET ?? [];
        $originalPost = $_POST ?? [];

        $_GET = ['start' => 0, 'lim' => 2];
        $_POST = [];

        $result = HTML::results(
            [
                [1, 'Alpha'],
                [2, 'Beta'],
                [3, 'Gamma'],
            ],
            'start',
            'lim',
            '/items',
            true,
            1,
            '',
            '#c0c0ff',
            'images/',
            'assets/',
            false,
            '',
            2
        );

        $this->assertStringContainsString('Showing 1-2 of 3 results.', $result);
        $this->assertStringContainsString('<table class="dev_table">', $result);
        $this->assertStringContainsString('Alpha', $result);
        $this->assertStringNotContainsString('Gamma', $result);

        $_GET = $originalGet;
        $_POST = $originalPost;
    }

    public function testNl2LiSkipsBlankLines()
    {
        $result = HTML::nl2li("Alpha\n\n Beta \n ");

        $this->assertSame("<li>Alpha</li>\n<li> Beta </li>\n", $result);
    }

    public function testBr2NlNormalizesCommonBreakTags()
    {
        $result = HTML::br2nl("Alpha<br>Beta<BR />Gamma<br/>Delta");

        $this->assertSame("Alpha\nBeta\nGamma\nDelta", $result);
    }
}
