<?php

namespace BlueFission\Tests;

use PHPUnit\Framework\TestCase;
use BlueFission\Func;

class FuncTest extends ValTest
{
    public function testConstructValidCallable()
    {
        $func = new Func(function() { return 'test'; });
        $this->assertInstanceOf(Func::class, $func);
        $this->assertEquals('test', $func());
    }

    public function testConstructInvalidCallable()
    {
        $func = new Func('not_callable');
        $this->expectException(\Exception::class);
        $func();

    }

    public function testConstructInvalidCallableArray()
	{
		$func = new Func(['not_callable']);
		$this->expectException(\Exception::class);
		$func();

	}

	public function testConstructInvalidCallableObject()
	{
		$func = new Func(new \stdClass());
		$this->expectException(\Exception::class);
		$func();

	}

	public function testConstructCallableString()
	{
		$func = new Func('strlen');
		$this->assertInstanceOf(Func::class, $func);
		$this->assertEquals(4, $func('test'));
	}

	public function testConstructCallableArray()
	{
		$func = new Func([new class() { public function test() { return 'test'; } }, 'test']);
		$this->assertInstanceOf(Func::class, $func);
		$this->assertEquals('test', $func());
	}

	public function testConstructCallableObject()
	{
		$func = new Func(new class() { public function test() { return 'test'; } });
		$this->assertInstanceOf(Func::class, $func);

		$this->expectException(\Exception::class);
		$func();
	}

	public function testConstructCallableStatic()
	{
		$func = new Func([new class() { public static function test() { return 'test'; } }, 'test']);
		$this->assertInstanceOf(Func::class, $func);
		$this->assertEquals('test', $func());
	}

	public function testConstructCallableClosure()
	{
		$func = new Func(function() { return 'test'; });
		$this->assertInstanceOf(Func::class, $func);
		$this->assertEquals('test', $func());
	}

	public function testConstructCallableClosureWithArgsAndReturn()
	{
		$func = new Func(function($arg) { return $arg; });
		$this->assertInstanceOf(Func::class, $func);
		$this->assertEquals('test', $func('test'));
	}

    public function testCast()
    {
        $func = new Func(function() { return 'casted'; });
        $castedFunc = $func->cast();
        $this->assertEquals('casted', $castedFunc());
    }

    public function testBind()
    {
        $object = new class() { public function test() { return 'bound'; } };
        $func = new Func(function() { return $this->test(); });
        $boundFunc = $func->bind($object);
        $this->assertEquals('bound', $boundFunc());
    }

    public function testExpects()
    {
        $func = new Func(function($arg1, $arg2) {});
        $expects = $func->expects();
        $this->assertCount(2, $expects);
        $this->assertContainsOnlyInstancesOf(\ReflectionParameter::class, $expects);
    }

    public function testReturns()
    {
        $func = new Func(function(): int { return 1; });
        $this->assertEquals('int', (string) $func->returns());
    }
}
