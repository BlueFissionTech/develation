<?php

namespace BlueFission\System\Tests;

use PHPUnit\Framework\TestCase;
use BlueFission\System\Process;

class ProcessTest extends \PHPUnit\Framework\TestCase
{
    private function command(): string
    {
        return 'php -v';
    }

    public function testStartProcess()
    {
        $process = new Process($this->command());
        $process->start();

        $this->assertTrue(is_resource($process->process));
    }

    public function testOutput()
    {
        $process = new Process($this->command());
        $process->start();

        $this->assertTrue(is_string($process->output()));
    }

    public function testStatus()
    {
        $process = new Process($this->command());
        $process->start();

        $this->assertTrue(is_bool($process->status()));
    }

    public function testWindowsSafeModeOptionDoesNotBreakOutput()
    {
        $process = new Process($this->command(), null, null, null, ['windows_safe' => true]);
        $process->start();
        $output = $process->output();
        $process->stop();

        $this->assertTrue(is_string($output));
        $this->assertNotNull($output);
    }
}
