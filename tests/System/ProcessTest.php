<?php

namespace BlueFission\System\Tests;

use PHPUnit\Framework\TestCase;
use BlueFission\System\Process;

class ProcessTest extends \PHPUnit\Framework\TestCase
{
    private function command(string $code = 'echo PHP_VERSION;'): array
    {
        return [PHP_BINARY, '-r', $code];
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

    public function testClosePreservesSuccessfulExitCodeAfterStatusPollingAndOutput()
    {
        $process = new Process($this->command('fwrite(STDOUT, "completed"); exit(0);'));
        $process->start();

        $this->waitForProcess($process);

        $this->assertSame('completed', $process->output());
        $this->assertSame(0, $process->close());
        $this->assertSame(0, $process->close());
    }

    public function testClosePreservesFailingExitCodeAfterStatusPollingAndOutput()
    {
        $process = new Process($this->command('fwrite(STDOUT, "failed"); exit(7);'));
        $process->start();

        $this->waitForProcess($process);

        $this->assertSame('failed', $process->output());
        $this->assertSame(7, $process->close());
    }

    private function waitForProcess(Process $process): void
    {
        $deadline = microtime(true) + 5;
        while ($process->status() && microtime(true) < $deadline) {
            usleep(10000);
        }

        $this->assertFalse($process->status(), 'Process did not terminate before the test timeout.');
    }
}
