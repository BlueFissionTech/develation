<?php

namespace BlueFission\Tests;

use BlueFission\Utils\Loader;
use PHPUnit\Framework\TestCase;

class LoaderTest extends TestCase
{
    public function testInstanceIsSingleton()
    {
        $loader1 = Loader::instance();
        $loader2 = Loader::instance();
        $this->assertSame($loader1, $loader2);
    }

    public function testConfigReturnsCorrectValue()
    {
        $loader = Loader::instance();
        $this->assertEquals('php', $loader->config('default_extension'));
    }

    public function testConfigSetsValue()
    {
        $loader = Loader::instance();
        $loader->config('default_extension', 'js');
        $this->assertEquals('js', $loader->config('default_extension'));
    }

    public function testLoadClass()
    {
        $this->expectException(\Exception::class);
        $loader = Loader::instance();
        $loader->load('NotExistingClass');
    }
}
