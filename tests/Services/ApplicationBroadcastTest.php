<?php

namespace BlueFission\Tests\Services;

use BlueFission\Behavioral\Behaviors\Behavior;
use BlueFission\Services\Application;
use PHPUnit\Framework\TestCase;

class ApplicationBroadcastTest extends TestCase
{
    public function testFreshBroadcastsRecoverAfterRecipientThrows(): void
    {
        $app = $this->application('fresh');
        $sink = new class {
            public int $calls = 0;

            public function hit(): void
            {
                $this->calls++;
                if ($this->calls === 1) {
                    throw new \RuntimeException('first delivery failed');
                }
            }
        };

        $app->delegate('sink', $sink);
        $app->route($app->name(), 'sink', new Behavior('hit'), 'hit');

        try {
            $this->broadcast($app, 'hit');
            self::fail('The first recipient exception must propagate.');
        } catch (\RuntimeException $exception) {
            self::assertSame('first delivery failed', $exception->getMessage());
        }

        $this->broadcast($app, 'hit');
        $this->broadcast($app, 'hit');

        self::assertSame(3, $sink->calls);
    }

    public function testIndependentRouteStillDeliversAfterException(): void
    {
        $app = $this->application('independent');
        $sink = new class {
            public int $otherCalls = 0;

            public function hit(): void
            {
                throw new \RuntimeException('failed');
            }

            public function other(): void
            {
                $this->otherCalls++;
            }
        };

        $app->delegate('sink', $sink);
        $app->route($app->name(), 'sink', new Behavior('hit'), 'hit');
        $app->route($app->name(), 'sink', new Behavior('other'), 'other');

        try {
            $this->broadcast($app, 'hit');
            self::fail('The recipient exception must propagate.');
        } catch (\RuntimeException $exception) {
            self::assertSame('failed', $exception->getMessage());
        }

        $this->broadcast($app, 'other');
        self::assertSame(1, $sink->otherCalls);
    }

    public function testNestedExceptionUnwindsBothBroadcasts(): void
    {
        $app = $this->application('nested');
        $sink = new class($app) {
            public int $outerCalls = 0;
            public int $innerCalls = 0;

            public function __construct(private Application $app)
            {
            }

            public function outer(): void
            {
                $this->outerCalls++;
                $inner = new Behavior('inner');
                $inner->target = $this->app;
                $this->app->broadcast($inner);
            }

            public function inner(): void
            {
                $this->innerCalls++;
                if ($this->innerCalls === 1) {
                    throw new \RuntimeException('nested delivery failed');
                }
            }
        };

        $app->delegate('sink', $sink);
        $app->route($app->name(), 'sink', new Behavior('outer'), 'outer');
        $app->route($app->name(), 'sink', new Behavior('inner'), 'inner');

        try {
            $this->broadcast($app, 'outer');
            self::fail('The nested recipient exception must propagate.');
        } catch (\RuntimeException $exception) {
            self::assertSame('nested delivery failed', $exception->getMessage());
        }

        $this->broadcast($app, 'outer');
        self::assertSame(2, $sink->outerCalls);
        self::assertSame(2, $sink->innerCalls);
    }

    private function application(string $suffix): Application
    {
        return Application::getInstance('BroadcastException_' . $suffix . '_' . uniqid());
    }

    private function broadcast(Application $app, string $name): void
    {
        $behavior = new Behavior($name);
        $behavior->target = $app;
        $app->broadcast($behavior);
    }
}
