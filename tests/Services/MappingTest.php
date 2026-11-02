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

    public function testAddNormalizesMethodPathAndNameWithPrimitiveObjects()
    {
        $mapping = Mapping::add(' /admin/users/ ', function () {}, ' users.index ', ' POST ');

        $this->assertEquals('post', $mapping->method);
        $this->assertEquals('admin/users', $mapping->path);
        $this->assertEquals('users.index', $mapping->name);
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

    public function testExplicitTargetKeepsApplicationsIsolatedAndRetainsNormalization(): void
    {
        $first = new App(['name' => 'mapping-first']);
        $second = new App(['name' => 'mapping-second']);
        $callback = static fn () => 'first';

        $mapping = Mapping::add(' /ready/ ', $callback, ' ready ', ' POST ', $first);
        Mapping::add('/other', static fn () => 'second', 'other', 'get', $second);

        $this->assertSame($mapping, $first->maps()['post']['ready']);
        $this->assertSame($callback, $mapping->callable);
        $this->assertSame('ready', $mapping->name);
        $this->assertArrayNotHasKey('get', $first->maps());
        $this->assertArrayNotHasKey('post', $second->maps());
        $this->assertSame([], App::instance()->maps());
    }

    public function testScopedRegistrationRestoresNestedAndExceptionalTargets(): void
    {
        $first = new App(['name' => 'mapping-scope-first']);
        $second = new App(['name' => 'mapping-scope-second']);

        $result = Mapping::withApplication($first, function () use ($first, $second) {
            Mapping::add('/first', static fn () => 1);
            $nested = Mapping::withApplication($second, function () use ($first) {
                Mapping::add('/second', static fn () => 2);
                Mapping::add('/explicit', static fn () => 3, '', 'get', $first);
                return 'nested result';
            });
            $this->assertSame('nested result', $nested);

            try {
                Mapping::withApplication($second, static function () {
                    Mapping::add('/before-error', static fn () => 4);
                    throw new \RuntimeException('registration failed');
                });
                $this->fail('Expected a registration exception.');
            } catch (\RuntimeException $exception) {
                $this->assertSame('registration failed', $exception->getMessage());
            }

            Mapping::add('/restored', static fn () => 5);
            return 'outer result';
        });

        $this->assertSame('outer result', $result);
        $this->assertSame(['first', 'explicit', 'restored'], array_keys($first->maps()['get']));
        $this->assertSame(['second', 'before-error'], array_keys($second->maps()['get']));
        Mapping::add('/legacy-after', static fn () => 6);
        $this->assertArrayHasKey('legacy-after', App::instance()->maps()['get']);
    }

    public function testCrudUsesExplicitAndScopedTargetsWithoutChangingGateways(): void
    {
        $first = new App(['name' => 'mapping-crud-first']);
        $second = new App(['name' => 'mapping-crud-second']);

        Mapping::withApplication($first, function () use ($second) {
            Mapping::crud('/alpha', 'items', 'Controller', 'id', 'first-gateway');
            Mapping::crud('/beta', 'items', 'Controller', 'key', 'second-gateway', $second);
        });

        $this->assertCount(3, $first->maps());
        $this->assertSame(['first-gateway'], $first->maps()['get']['alpha/items']->gateways());
        $this->assertSame(['first-gateway'], $first->maps()['delete']['alpha/items/$id']->gateways());
        $this->assertSame(['second-gateway'], $second->maps()['get']['beta/items']->gateways());
        $this->assertSame(['second-gateway'], $second->maps()['delete']['beta/items/$key']->gateways());
        $this->assertArrayNotHasKey('beta/items', $first->maps()['get']);
        $this->assertSame([], App::instance()->maps());
    }

    public function testFiberRegistrationDoesNotLeakIntoMainOrOtherFibers(): void
    {
        $first = new App(['name' => 'mapping-fiber-first']);
        $second = new App(['name' => 'mapping-fiber-second']);

        $fiber = new \Fiber(static function () use ($first) {
            return Mapping::withApplication($first, static function () {
                Mapping::add('/fiber-before', static fn () => 1);
                \Fiber::suspend();
                Mapping::add('/fiber-after', static fn () => 2);
            });
        });

        $fiber->start();
        Mapping::withApplication($second, static function () {
            Mapping::add('/main', static fn () => 3);
        });
        $other = new \Fiber(static function () {
            Mapping::add('/other-fiber', static fn () => 4);
        });
        $other->start();
        $fiber->resume();

        $this->assertSame(['fiber-before', 'fiber-after'], array_keys($first->maps()['get']));
        $this->assertSame(['main'], array_keys($second->maps()['get']));
        $this->assertSame(['other-fiber'], array_keys(App::instance()->maps()['get']));
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
