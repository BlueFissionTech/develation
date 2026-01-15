<?php
namespace BlueFission\Tests;

use BlueFission\Cli\Util\Screen;

class ScreenTest extends \PHPUnit\Framework\TestCase
{
    public function testRewriteLine()
    {
        $this->assertSame("\r\033[2KHello", Screen::rewriteLine('Hello'));
    }

    public function testMoveCursor()
    {
        $this->assertSame("\033[3;5H", Screen::moveCursor(5, 3));
    }
}
