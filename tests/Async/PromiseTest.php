<?php

namespace BlueFission\Async\Tests;

use PHPUnit\Framework\TestCase;
use BlueFission\Async\Promise;

class PromiseTest extends TestCase {
    public function testPromiseResolution() {
        $expectedResult = "Success!";
        $wasCalled = false;

        $promise = new Promise(function ($resolve, $reject) use ($expectedResult) {
            $resolve($expectedResult);
        });

        $promise->then(function ($result) use (&$wasCalled, $expectedResult) {
            $wasCalled = true;
            $this->assertEquals($expectedResult, $result);
        });

        $promise->try();

        $this->assertTrue($wasCalled, "The fulfillment callback should have been called.");
    }

    public function testPromiseRejection() {
        $expectedReason = new \Exception("Error!");
        $wasCalled = false;

        $promise = new Promise(function ($resolve, $reject) use ($expectedReason) {
            $reject($expectedReason);
        });

        $promise->then(function($result) {}, function ($reason) use (&$wasCalled, $expectedReason) {
            $wasCalled = true;
            $this->assertSame($expectedReason, $reason);
        });

        $promise->try();

        $this->assertTrue($wasCalled, "The rejection callback should have been called.");
    }
}
