<?php
namespace BlueFission\Tests;

use BlueFission\Cli\Console;

class ConsoleTest extends \PHPUnit\Framework\TestCase
{
    public function testWriteUsesOutputHandler()
    {
        $buffer = '';
        $console = new Console([
            'outputHandler' => function ($text) use (&$buffer) {
                $buffer .= $text;
            },
        ]);

        $console->write('Hello');
        $console->writeln('World');

        $this->assertSame('Hello' . 'World' . PHP_EOL, $buffer);
        $this->assertSame('World' . PHP_EOL, $console->lastOutput());
    }

    public function testConfirmUsesInputHandler()
    {
        $console = new Console([
            'inputHandler' => function () {
                return 'yes';
            },
        ]);

        $this->assertTrue($console->confirm('Continue?', false));
    }
}
