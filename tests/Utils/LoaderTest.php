<?php
namespace BlueFission\Tests;

use BlueFission\Utils\Loader;
use PHPUnit\Framework\TestCase;

class LoaderTest extends TestCase
{
    public function testInstanceIsSingleton()
    {
        $loader1 = BlueFission\Utils\Loader::instance();
        $loader2 = BlueFission\Utils\Loader::instance();
        $this->assertSame($loader1, $loader2);
    }
    
    public function testConfigReturnsCorrectValue()
    {
        $loader = BlueFission\Utils\Loader::instance();
        $this->assertEquals('php', $loader->config('default_extension'));
    }
    
    public function testConfigSetsValue()
    {
        $loader = BlueFission\Utils\Loader::instance();
        $loader->config('default_extension', 'js');
        $this->assertEquals('js', $loader->config('default_extension'));
    }
    
    public function testAddPath()
    {
        $loader = BlueFission\Utils\Loader::instance();
        $loader->addPath('/test/path');
        $paths = $loader->config('paths');
        $this->assertContains('/test/path', $paths);
    }
    
    public function testLoadClass()
    {
        $this->expectException(\Exception::class);
        $loader = BlueFission\Utils\Loader::instance();
        $loader->load('NotExistingClass');
    }
}
