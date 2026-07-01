<?php

namespace BlueFission\Tests\Data;

use BlueFission\Arr;
use BlueFission\Data\Log;
use BlueFission\Obj;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

class LogTest extends TestCase
{
    public function testMessageUsesArrSliceAndFilterPipeline(): void
    {
        $log = new Log(['instant' => true]);

        $data = new ReflectionProperty(Obj::class, '_data');
        $data->setAccessible(true);
        $data->setValue($log, Arr::make([
            '2026-07-01 10:00:00' => 'first',
            '2026-07-01 10:01:00' => '',
            '2026-07-01 10:02:00' => 'third',
        ]));

        $message = new ReflectionMethod(Log::class, 'message');
        $message->setAccessible(true);

        $this->assertSame(
            "2026-07-01 10:02:00 - third\n",
            $message->invoke($log, 2)
        );
    }
}
