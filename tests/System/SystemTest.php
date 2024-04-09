<?php

use BlueFission\System\System;
use PHPUnit\Framework\TestCase;

class SystemTest extends TestCase
{
    /**
     * Test the `isValidCommand` method.
     */
    public function testIsValidCommand()
    {
        $system = new System();
        $this->assertTrue($system->isValidCommand('echo "Hello World"'));
        $this->assertFalse($system->isValidCommand(''));

        $validCommand = 'ls';
        $invalidCommand = 'notacommand';

        $this->assertTrue($system->isValidCommand($validCommand));
        $this->assertFalse($system->isValidCommand($invalidCommand));
    }

    /**
     * Test the `run` method.
     */
    public function testRun()
    {
        $system = new System();
        $command = 'echo "Hello World"';

        // Test without any options
        $system->run($command);
        $this->assertNotEmpty($system->process());
        $this->assertEquals('Hello World' . PHP_EOL, $system->_response);

        // Test with background option
        $system->run($command, true);
        $this->assertNotEmpty($system->process());
        $this->assertEquals('', $system->_response);

        // Test with additional options
        $system->run($command, false, ['-n']);
        $this->assertNotEmpty($system->process());
        $this->assertEquals('Hello World', $system->_response);

        // Test with an invalid command
        try {
            $system->run('', false, []);
            $this->fail('Expected exception not thrown');
        } catch (\BadArgumentException $e) {
            $this->assertEquals('Command cannot be empty!', $e->getMessage());
        }

        try {
            $system->run('invalid_command', false, []);
            $this->fail('Expected exception not thrown');
        } catch (\BadArgumentException $e) {
            $this->assertEquals('Invalid command!', $e->getMessage());
        }
    }

    /**
     * Test the `cwd` method.
     */
    public function testCwd()
    {
        $system = new System();
        $system->cwd('/');
        $this->assertEquals('/', $system->cwd());
    }

}
