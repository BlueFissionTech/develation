<?php

namespace BlueFission\Tests\Services;

use BlueFission\Services\Mapping;
use PHPUnit\Framework\TestCase;

class MappingTest extends TestCase
{
    public function testAddMethod()
    {
        $path = '/test';
        $callable = function() {};
        $name = 'test';
        $method = 'get';

        $mapping = Mapping::add($path, $callable, $name, $method);

        $this->assertInstanceOf(Mapping::class, $mapping);
        $this->assertEquals('get', $mapping->method);
        $this->assertEquals('test', $mapping->name);
        $this->assertEquals('/test', $mapping->path);
        $this->assertEquals($callable, $mapping->callable);
    }

    public function testCrudMethod()
    {
        $root = '/test';
        $package = 'package';
        $controller = 'Controller';
        $idField = 'id';
        $gateway = 'gateway';

        Mapping::crud($root, $package, $controller, $idField, $gateway);

        $app = \App::instance();
        $maps = $app->maps();

        $this->assertCount(4, $maps);
        $this->assertEquals('/testpackage', $maps[0]->path);
        $this->assertEquals('test.package', $maps[0]->name);
        $this->assertEquals('get', $maps[0]->method);
        $this->assertEquals(['gateway'], $maps[0]->gateways());
        $this->assertEquals('/testpackage/{$id}', $maps[1]->path);
        $this->assertEquals('test.package.get', $maps[1]->name);
        $this->assertEquals('get', $maps[1]->method);
        $this->assertEquals(['gateway'], $maps[1]->gateways());
        $this->assertEquals('/testpackage', $maps[2]->path);
        $this->assertEquals('test.package.save', $maps[2]->name);
        $this->assertEquals('post', $maps[2]->method);
        $this->assertEquals(['gateway'], $maps[2]->gateways());
        $this->assertEquals('/testpackage/{$id}', $maps[3]->path);
        $this->assertEquals('test.package.update', $maps[3]->name);
        $this->assertEquals('post', $maps[3]->method);
        $this->assertEquals(['gateway'], $maps[3]->gateways());
    }

    public function testGatewayMethod1()
    {
        $path = '/test';
        $callable = function() {};
        $name = 'test';
        $method = 'get';

        $mapping = Mapping::add($path, $callable, $name, $method);
        $mapping->gateway('gateway');

        $this->assertEquals(['gateway'], $mapping->gateways());
    }

    /**
     * Test if the gateway method is working as expected
     * 
     * @return void
     */
    public function testGatewayMethod2()
    {
        $mapping = new Mapping();
        $mapping->gateway("test_gateway");
        $gateways = $mapping->gateways();
        
        $this->assertCount(1, $gateways);
        $this->assertEquals("test_gateway", $gateways[0]);
    }
}