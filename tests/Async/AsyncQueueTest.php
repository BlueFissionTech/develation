<?php

namespace BlueFission\Async\Tests;

use BlueFission\Async\Async;
use BlueFission\Data\Queues\SplPriorityQueue;
use PHPUnit\Framework\TestCase;

class AsyncQueueTest extends TestCase
{
    public function testDefaultQueueWaitsForExplicitSynchronousDrain(): void
    {
        LocalAsyncFixture::setQueue(SplPriorityQueue::class);
        $events = [];

        $promise = LocalAsyncFixture::exec(function ($resolve, $reject) use (&$events) {
            $events[] = 'task';
            $resolve('complete');
        });
        $promise->then(function ($result) use (&$events) {
            $events[] = $result;
        });

        $this->assertSame([], $events);

        LocalAsyncFixture::run();

        $this->assertSame(['task', 'complete'], $events);
    }
}

class LocalAsyncFixture extends Async
{
}
