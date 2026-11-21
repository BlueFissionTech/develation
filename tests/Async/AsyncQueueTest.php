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

    public function testDefaultQueueDrainsTasksSequentiallyInTheCallingProcess(): void
    {
        LocalAsyncFixture::setQueue(SplPriorityQueue::class);
        $events = [];

        LocalAsyncFixture::exec(function ($resolve) use (&$events): void {
            $events[] = 'first start';
            $resolve('first done');
        })->then(function ($result) use (&$events): void {
            $events[] = $result;
        });
        LocalAsyncFixture::exec(function ($resolve) use (&$events): void {
            $events[] = 'second start';
            $resolve('second done');
        })->then(function ($result) use (&$events): void {
            $events[] = $result;
        });

        $this->assertSame([], $events);
        LocalAsyncFixture::run();
        $this->assertSame(['first start', 'first done', 'second start', 'second done'], $events);

        LocalAsyncFixture::run();
        $this->assertCount(4, $events); // Completed tasks are not replayed by another drain.
    }

    public function testRejectedPromiseDoesNotPreventNextQueuedTask(): void
    {
        LocalAsyncFixture::setQueue(SplPriorityQueue::class);
        $events = [];

        LocalAsyncFixture::exec(function ($resolve, $reject) use (&$events): void {
            $events[] = 'failed task';
            $reject('expected failure');
        })->then(
            function () use (&$events): void { $events[] = 'unexpected success'; },
            function ($reason) use (&$events): void { $events[] = $reason; }
        );
        LocalAsyncFixture::exec(function ($resolve) use (&$events): void {
            $events[] = 'next task';
            $resolve('done');
        })->then(function ($result) use (&$events): void {
            $events[] = $result;
        });

        LocalAsyncFixture::run();
        $this->assertSame(['failed task', 'expected failure', 'next task', 'done'], $events);
    }
}

class LocalAsyncFixture extends Async
{
}
