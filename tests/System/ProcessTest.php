<?php
namespace BlueFission\Tests;

use PHPUnit\Framework\TestCase;
use BlueFission\System\Process;

class ProcessTest extends \PHPUnit\Framework\TestCase
{
    public function testStartProcess()
    {
        $process = new Process("ls");
        $process->start();

        $this->assertTrue(is_resource($process->process));
    }

    public function testOutput()
    {
        $process = new Process("ls");
        $process->start();

        $this->assertTrue(is_string($process->output()));
    }

    public function testStatus()
    {
        $process = new Process("ls");
        $process->start();

        $this->assertTrue(is_bool($process->status()));
    }

    public function testStopProcess()
    {
        $process = new Process("ls");
        $process->start();

        $this->assertTrue(is_int($process->stop()));
    }

    public function testCloseProcess()
    {
        $process = new Process("ls");
        $process->start();

        $this->assertTrue(is_int($process->close()));
    }
}
