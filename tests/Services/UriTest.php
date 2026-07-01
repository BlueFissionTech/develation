<?php

namespace BlueFission\Tests;

use BlueFission\Services\Uri;
use BlueFission\Net\HTTP;

class UriTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructor()
    {
        $uri = new Uri('https://www.example.com/foo/bar');
        $this->assertEquals('foo/bar', $uri->path);
        $this->assertEquals(['foo', 'bar'], $uri->parts);
    }

    public function testMatch()
    {
        $uri = new Uri('https://www.example.com/foo/bar');
        $this->assertTrue($uri->match('/foo/bar'));
        $this->assertFalse($uri->match('/foo/baz'));
    }

    public function testMatchAndReturn()
    {
        $uri = new Uri('https://www.example.com/foo/bar');
        $this->assertEquals('/foo/bar', $uri->matchAndReturn('/foo/bar'));
        $this->assertFalse($uri->matchAndReturn('/foo/baz'));
    }

    public function testBuildArguments()
    {
        $uri = new Uri('https://www.example.com/foo/bar');
        $this->assertEquals(['id' => 'bar'], $uri->buildArguments('/foo/$id'));
    }

    public function testMatchSupportsValueTokens()
    {
        $uri = new Uri('https://www.example.com/users/42/profile');

        $this->assertTrue($uri->match('/users/$id/profile'));
        $this->assertTrue($uri->match('/$resource/42/profile'));
        $this->assertFalse($uri->match('/users/42'));
    }

    public function testBuildArgumentsUsesStringHelpersForTokenNames()
    {
        $uri = new Uri('https://www.example.com/users/42/profile');

        $this->assertEquals(
            ['resource' => 'users', 'id' => '42'],
            $uri->buildArguments('/$resource/$id/profile')
        );
    }
}
