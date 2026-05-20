<?php

namespace BlueFission\Async\Tests;

use PHPUnit\Framework\TestCase;
use BlueFission\Data\Queues\SplPriorityQueue;
use BlueFission\Async\Fork;
use BlueFission\Tests\Support\TestEnvironment;

class ForkTest extends TestCase
{
    public function testProcessForking()
    {
        if (!function_exists('pcntl_fork')) {
            $this->markTestSkipped('The pcntl extension is not available');
        }

        Fork::setQueue(SplPriorityQueue::class);

        $tempDir = TestEnvironment::tempDir('bf_fork');
        $resultFile = $tempDir . DIRECTORY_SEPARATOR . 'result.txt';

        try {
            $processId = null;

            $task = function () use ($resultFile) {
                file_put_contents($resultFile, 'executed');
            };

            Fork::do($task, 10, $processId);
            Fork::run();

            if ($processId) {
                pcntl_waitpid($processId, $status);
            }

            $this->assertFileExists($resultFile, 'The forked task should write its result file');
            $this->assertSame('executed', file_get_contents($resultFile));
        } finally {
            TestEnvironment::removeDir($tempDir);
        }
    }
}
