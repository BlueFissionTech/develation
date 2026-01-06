<?php
namespace BlueFission\Tests\Connections;

use BlueFission\Connections\Stream;
use BlueFission\Net\HTTP;
use BlueFission\Tests\Support\TestEnvironment;

require_once __DIR__ . '/../Support/TestEnvironment.php';
 
class StreamTest extends ConnectionTest {
 
 	static $classname = 'BlueFission\Connections\Stream';

 	public function setUp(): void
 	{
 		if (!TestEnvironment::isNetworkEnabled()) {
 			$this->markTestSkipped('Network tests are disabled');
 		}
 		parent::setUp();
 	}

 	public function testOpen()
    {
        $target = getenv('DEV_ELATION_STREAM_TEST_URL') ?: 'https://bluefission.com';
        if (!HTTP::urlExists($target)) {
            $this->markTestSkipped('Stream target is not reachable');
        }

        $stream = new Stream();
        $stream->open();

        $this->assertEquals(Stream::STATUS_NOTCONNECTED, $stream->status());

        $stream->config('target', $target);
        $stream->open();

        $this->assertEquals(Stream::STATUS_CONNECTED, $stream->status());
    }

    public function testQuery()
    {
        $target = getenv('DEV_ELATION_STREAM_TEST_URL') ?: 'https://bluefission.com';
        if (!HTTP::urlExists($target)) {
            $this->markTestSkipped('Stream target is not reachable');
        }

        $stream = new Stream();
        $stream->open();

        $this->assertEquals(Stream::STATUS_NOTCONNECTED, $stream->status());

        $stream->config('target', $target);
        $stream->open();
        $stream->query('test');

        $this->assertEquals(Stream::STATUS_SUCCESS, $stream->status());

        $stream->config('method', 'GET');
        $stream->query();

        $this->assertEquals(Stream::STATUS_SUCCESS, $stream->status());
    }
}
