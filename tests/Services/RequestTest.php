<?php

namespace BlueFission\Tests\Services;

use BlueFission\Services\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testAll()
    {
        $request = new Request();

        $this->assertInternalType('array', $request->all());
    }

    public function testType()
    {
        $request = new Request();

        $this->assertInternalType('string', $request->type());
    }

    public function testSet()
    {
        $request = new Request();

        $this->expectException(\Exception::class);

        $request->field = 'value';
    }
}
