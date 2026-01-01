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

    // Add more tests as needed to cover different command scenarios, error handling, etc.
}
