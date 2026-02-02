<?php
namespace BlueFission\Tests;

use BlueFission\Cli\Util\Working;

class WorkingTest extends \PHPUnit\Framework\TestCase
{
    public function testRunReturnsResult()
    {
        $working = new Working('Work', ['.', 'o'], 10, function ($text) {
            // suppress output
        });

        $result = $working->run(function () {
            return 'done';
        });

        $this->assertSame('done', $result);
    }

    public function testRunConsumesGenerator()
    {
        $buffer = '';
        $working = new Working('Spin', ['.', 'o'], 10, function ($text) use (&$buffer) {
            $buffer .= $text;
        });

        $result = $working->run(function () {
            yield 1;
            yield 2;
            return 'complete';
        });

        $this->assertSame('complete', $result);
        $this->assertNotSame('', $buffer);
    }
}
