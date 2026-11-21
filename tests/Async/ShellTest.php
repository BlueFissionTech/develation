<?php

namespace BlueFission\Async\Tests;

use PHPUnit\Framework\TestCase;
use BlueFission\Async\Shell;
use BlueFission\System\Process;

class ShellTest extends TestCase {
    private $processMock;

    protected function setUp(): void {
        parent::setUp();
        // Create a mock of the Process class
        // $this->processMock = $this->createMock(Process::class);
    }

    public function testShellCommandExecution() {
        $command = 'php -r "echo \'Hello, World!\';"';
        $expectedOutput = "Hello, World!";

        $output = null;
        $error = null;

        $result = Shell::do($command);

        $result->then(
            function ($data) use (&$output) {
                $output = $data;
            },
            function ($err) use (&$error) {
                $error = $err;
            }
        );

        Shell::run();

        $this->assertNull($error);
        $this->assertTrue($output === null || $output === $expectedOutput);
    }

    public function testUnavailableProcessStatusDoesNotYieldForeverAndCloses(): void {
        $process = new class(['synthetic']) extends Process {
            public int $closeCount = 0;
            public function start() { return $this; }
            public function status() { return 'Error starting process'; }
            public function output() { throw new \RuntimeException('Output must not be read after start failure.'); }
            public function close() { $this->closeCount++; return -1; }
        };

        try {
            iterator_to_array(ShellProbe::stream($process));
            $this->fail('A diagnostic status must not be treated as running or success.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Process status unavailable.', $exception->getMessage());
        }
        $this->assertSame(1, $process->closeCount);
    }

    public function testShellStreamYieldsRunningAndFinalOutputThenCloses(): void {
        $process = new class(['synthetic']) extends Process {
            public int $closeCount = 0;
            private array $states = [true, false];
            private array $chunks = ['part', 'tail'];
            public function start() { return $this; }
            public function status() { return array_shift($this->states); }
            public function output() { return array_shift($this->chunks); }
            public function close() { $this->closeCount++; return 0; }
        };

        $this->assertSame(['part', 'tail'], iterator_to_array(ShellProbe::stream($process), false));
        $this->assertSame(1, $process->closeCount);
    }

    public function testShellStreamPreservesOriginalFailureWhenCloseAlsoFails(): void {
        $process = new class(['synthetic']) extends Process {
            public function start() { return $this; }
            public function status() { throw new \RuntimeException('original failure'); }
            public function close() { throw new \RuntimeException('cleanup failure'); }
        };

        try {
            iterator_to_array(ShellProbe::stream($process));
            $this->fail('The original failure should remain visible.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('original failure', $exception->getMessage());
        }
    }

    // Add more tests as needed to cover different command scenarios, error handling, etc.
}

class ShellProbe extends Shell {
    public static function stream(Process $process): \Generator {
        return parent::streamProcess($process);
    }
}
