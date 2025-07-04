<?php

namespace BlueFission\Tests\Services;

use BlueFission\Services\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testAll()
    {
        $request = new Request();

        //assertInternalType is not defined, so let's do this correctly
        $this->assertIsArray($request->all());
    }

    public function testType()
    {
        $request = new Request();

        $this->assertIsString($request->type());
    }

    public function testSet()
    {
        $request = new Request();

        $this->expectException(\Exception::class);

        $request->field = 'value';

        $this->assertEquals('value', $request->field);
    }

    public function testGet()
    {
        $request = new Request();

        $this->expectException(\Exception::class);

        $request->field = 'value';

        $this->assertEquals('value', $request->get('field'));
    }

    public function testGetType()
    {
        $request = new Request();

        $this->assertEquals('GET', $request->type());
    }

    public function testGetUri()
    {
        $request = new Request();

        $this->assertEquals('/', $request->uri());
    }
}
