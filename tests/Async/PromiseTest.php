<?php

namespace BlueFission\Async\Tests;

use PHPUnit\Framework\TestCase;
use BlueFission\Async\Promise;
use BlueFission\Async\Async;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Obj;

class PromiseTest extends TestCase {
    private $asyncInstance;

    protected function setUp(): void {
        // $this->asyncInstance = $this->createMock(Obj::class);
        // Create a mock of IAsync interface
        $this->asyncInstance = $this->createMock(Async::class);
        $this->asyncInstance->expects($this->any())
                            ->method('perform');
        // If IAsync has perform method, configure it to accept any Event and perform actions
        $this->asyncInstance->method('perform')->willReturnCallback(function ($event, $meta = null) {
            // Optionally you can log or perform other checks here
        });
    }

    public function testPromiseResolution() {
        $expectedResult = "Success!";
        $wasCalled = false;
        $eventFired = false;

        $promise = new Promise(function ($resolve, $reject) use ($expectedResult) {
            $resolve($expectedResult);
        }, $this->asyncInstance);

        $promise->then(function ($result) use (&$wasCalled, $expectedResult) {
            $wasCalled = true;
            $this->assertEquals($expectedResult, $result);
        });

        $this->asyncInstance->when(Event::SUCCESS, function($behavior, $args) use (&$eventFired) {
            $eventFired = true;
        });
        
        $promise->try();

        $this->assertTrue($wasCalled, "The fulfillment callback should have been called.");
        // $this->assertTrue($eventFired, "The success event should have been fired.");

        // $this->asyncInstance->expects($this->once())->method('perform')->with($this->equalTo(Event::SUCCESS));
    }

    public function testPromiseRejection() {
        $expectedReason = new \Exception("Error!");
        $wasCalled = false;
        $eventFired = false;

        $promise = new Promise(function ($resolve, $reject) use ($expectedReason) {
            $reject($expectedReason);
        }, $this->asyncInstance);

        $promise->then(function($result) {}, function ($reason) use (&$wasCalled, $expectedReason) {
            $wasCalled = true;
            $this->assertSame($expectedReason, $reason);
        });

        $this->asyncInstance->when(Event::FAILURE, function($behavior, $args) use (&$eventFired) {
            $eventFired = true;
        });

        $promise->try();

        $this->assertTrue($wasCalled, "The rejection callback should have been called.");
        // $this->assertTrue($eventFired, "The success event should have been fired.");
        
        // $this->asyncInstance->expects($this->once())->method('perform')->with($this->equalTo(Event::FAILURE));
    }
}
