<?php

namespace BlueFission\System\Tests;

use PHPUnit\Framework\TestCase;
use BlueFission\System\Process;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Meta;

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

    public function testStartFailureDoesNotDiscloseCommandInLogOrErrorEvent(): void
    {
        $secret = 'synthetic-secret-argument-123';
        $process = new class([PHP_BINARY, '--token=' . $secret]) extends Process {
            protected function open(array $descriptorSpec)
            {
                return false;
            }
        };
        $events = [];
        $process->when(Event::ERROR, function ($behavior, $meta) use (&$events): void {
            $events[] = $meta;
        });

        $logFile = tempnam(sys_get_temp_dir(), 'bf_proc_log_');
        $originalLog = ini_get('error_log');
        try {
            ini_set('error_log', $logFile);
            $this->assertSame($process, $process->start());

            $this->assertCount(1, $events);
            $this->assertInstanceOf(Meta::class, $events[0]);
            $this->assertSame('Error starting process', $events[0]->info);
            $this->assertStringNotContainsString($secret, (string) file_get_contents($logFile));
            $this->assertStringContainsString('Error starting process', (string) file_get_contents($logFile));
        } finally {
            ini_set('error_log', $originalLog);
            @unlink($logFile);
        }
    }

    public function testFailedStartCannotBeObservedAsSuccessfulExit(): void
    {
        $process = new class($this->command()) extends Process {
            protected function open(array $descriptorSpec)
            {
                return false;
            }
        };

        $logFile = tempnam(sys_get_temp_dir(), 'bf_proc_log_');
        $originalLog = ini_get('error_log');
        try {
            ini_set('error_log', $logFile);
            $process->start();

            $this->assertSame('Error starting process', $process->status());
            $this->assertSame(-1, $process->close());
            $this->assertSame(-1, $process->close());
        } finally {
            ini_set('error_log', $originalLog);
            @unlink($logFile);
        }
    }

    public function testFailedStartRemovesOwnedCaptureFiles(): void
    {
        $process = new class($this->command(), null, null, null, ['windows_safe' => true]) extends Process {
            public array $createdCapturePaths = [];

            protected function isWindows(): bool
            {
                return true;
            }

            protected function prepareWindowsSafeCapture(): void
            {
                parent::prepareWindowsSafeCapture();
                $this->createdCapturePaths = [$this->_stdoutFile, $this->_stderrFile];
            }

            protected function open(array $descriptorSpec)
            {
                return false;
            }
        };

        $logFile = tempnam(sys_get_temp_dir(), 'bf_proc_log_');
        $originalLog = ini_get('error_log');
        try {
            ini_set('error_log', $logFile);
            $process->start();

            $this->assertCount(2, $process->createdCapturePaths);
            foreach ($process->createdCapturePaths as $path) {
                $this->assertFileDoesNotExist($path);
            }
            $this->assertSame('Error starting process', $process->status());
            $this->assertSame(-1, $process->close());
        } finally {
            ini_set('error_log', $originalLog);
            @unlink($logFile);
            foreach ($process->createdCapturePaths as $path) {
                @unlink($path);
            }
        }
    }

    public function testThrowingStartRemainsObservableWithoutAnExitCode(): void
    {
        $process = new class($this->command()) extends Process {
            protected function open(array $descriptorSpec)
            {
                throw new \RuntimeException('synthetic open failure');
            }
        };

        try {
            $process->start();
            $this->fail('The original open exception must remain visible.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('synthetic open failure', $exception->getMessage());
        }

        $this->assertSame('Error starting process', $process->status());
        $this->assertSame(-1, $process->close());
    }

    public function testStartExceptionSurvivesThrowingErrorListener(): void
    {
        $process = new class($this->command()) extends Process {
            protected function open(array $descriptorSpec)
            {
                throw new \RuntimeException('original start failure');
            }
        };
        $process->when(Event::ERROR, function (): void {
            throw new \RuntimeException('observer failure');
        });

        try {
            $process->start();
            $this->fail('The original start failure must remain visible.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('original start failure', $exception->getMessage());
        }

        $this->assertSame('Error starting process', $process->status());
        $this->assertSame(-1, $process->close());
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
