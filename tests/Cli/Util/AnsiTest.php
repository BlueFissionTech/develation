<?php
namespace BlueFission\Tests;

use BlueFission\Cli\Util\Ansi;

class AnsiTest extends \PHPUnit\Framework\TestCase
{
    public function testColorizeForceOn()
    {
        $colored = Ansi::colorize('Hello', 'red', [], true);
        $this->assertStringContainsString("\033[", $colored);
        $this->assertStringEndsWith(Ansi::RESET, $colored);
    }

    public function testColorizeForceOff()
    {
        $plain = Ansi::colorize('Hello', 'red', [], false);
        $this->assertSame('Hello', $plain);
    }

    public function testStripRemovesCodes()
    {
        $colored = Ansi::colorize('Hello', 'green', ['bold'], true);
        $this->assertSame('Hello', Ansi::strip($colored));
    }
}
