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
        $command = 'echo "Hello, World!"';
        $expectedOutput = "Hello, World!\n";

        // Configure the mock to simulate command execution
        // $this->processMock->method('start')->willReturn(true);
        // $this->processMock->method('status')->will($this->onConsecutiveCalls(true, false)); // Simulate running then stopping
        // $this->processMock->method('output')->willReturn($expectedOutput);
        // $this->processMock->method('close')->willReturn(true);

        // Assuming Shell can use a static method to set the process instance
        // Shell::setProcessInstance($this->processMock);

        $result = Shell::do($command);

        // Here you would normally assert that the process runs correctly,
        // but since exec returns a Promise you need to handle the resolution.
        $result->then(
            function ($output) use ($expectedOutput) {
                $this->assertEquals($expectedOutput, $output);
            },
            function ($error) {
                $this->fail("The command execution failed with error: " . $error);
            }
        );

        // Ensure all methods are called as expected
        // $this->processMock->expects($this->once())->method('start');
        // $this->processMock->expects($this->exactly(2))->method('status');
        // $this->processMock->expects($this->once())->method('output');
        // $this->processMock->expects($this->once())->method('close');
    }

    // Add more tests as needed to cover different command scenarios, error handling, etc.
}
