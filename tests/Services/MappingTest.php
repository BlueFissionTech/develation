<?php

namespace BlueFission\Tests\Services;

use BlueFission\Services\Mapping;
use BlueFission\Services\Application as App;
use PHPUnit\Framework\TestCase;

class MappingTest extends TestCase
{
    protected function setUp(): void
    {
        $ref = new \ReflectionClass(App::class);
        $prop = $ref->getProperty('_instances');
        $prop->setAccessible(true);
        $prop->setValue([]);
    }

    public function testAddMethod()
    {
        $path = '/test';
        $callable = function () {};
        $name = 'test';
        $method = 'get';

        $mapping = Mapping::add($path, $callable, $name, $method);

        $this->assertInstanceOf(Mapping::class, $mapping);
        $this->assertEquals('get', $mapping->method);
        $this->assertEquals('test', $mapping->name);
        $this->assertEquals('test', $mapping->path);
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

        $app = App::instance();
        $maps = $app->maps();

        $this->assertCount(3, $maps);
        // $this->assertEquals('test', $maps['get']['test']->path);
        // $this->assertEquals('test', $maps['get']['test']->name);
        // $this->assertEquals('get', $maps['get']['test']->method);
        // $this->assertEquals([], $maps['get']['test']->gateways());

        $this->assertEquals('test/package', $maps['get']['test/package']->path);
        $this->assertEquals('.testpackage', $maps['get']['test/package']->name);
        $this->assertEquals('get', $maps['get']['test/package']->method);
        $this->assertEquals(['gateway'], $maps['get']['test/package']->gateways());

        $this->assertEquals('test/package/$id', $maps['get']['test/package/$id']->path);
        $this->assertEquals('.testpackage.get', $maps['get']['test/package/$id']->name);
        $this->assertEquals('get', $maps['get']['test/package/$id']->method);
        $this->assertEquals(['gateway'], $maps['get']['test/package/$id']->gateways());

        $this->assertEquals('test/package', $maps['post']['test/package']->path);
        $this->assertEquals('.testpackage.save', $maps['post']['test/package']->name);
        $this->assertEquals('post', $maps['post']['test/package']->method);
        $this->assertEquals(['gateway'], $maps['post']['test/package']->gateways());

        $this->assertEquals('test/package/$id', $maps['post']['test/package/$id']->path);
        $this->assertEquals('.testpackage.update', $maps['post']['test/package/$id']->name);
        $this->assertEquals('post', $maps['post']['test/package/$id']->method);
        $this->assertEquals(['gateway'], $maps['post']['test/package/$id']->gateways());
    }

    public function testGatewayMethod1()
    {
        $path = '/test';
        $callable = function () {};
        $name = 'test';
        $method = 'get';

        $mapping = Mapping::add($path, $callable, $name, $method);
        $mapping->gateway('gateway');

        $this->assertEquals(['gateway'], $mapping->gateways());
    }

    public function testGatewayMethod1WithMultipleGateways()
    {
        $path = '/test';
        $callable = function() {};
        $name = 'test';
        $method = 'get';

        $mapping = Mapping::add($path, $callable, $name, $method);
        $mapping->gateway('gateway1');
        $mapping->gateway('gateway2');

        $this->assertEquals(['gateway1', 'gateway2'], $mapping->gateways());
    }

    public function testGatewayMethod1WithEmptyGateways()
    {
        $path = '/test';
        $callable = function() {};
        $name = 'test';
        $method = 'get';

        $mapping = Mapping::add($path, $callable, $name, $method);
        $mapping->gateway('');

        $this->assertEquals([], $mapping->gateways());
    }

    public function testGetwayMethodWithArray()
    {
        $path = '/test';
        $callable = function() {};
        $name = 'test';
        $method = 'get';

        $mapping = Mapping::add($path, $callable, $name, $method);
        $mapping->gateway(['gateway1', 'gateway2']);

        $this->assertEquals(['gateway1', 'gateway2'], $mapping->gateways());
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
