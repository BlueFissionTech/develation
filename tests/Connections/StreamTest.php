<?php
namespace BlueFission\Tests\Connections;

use BlueFission\Connections\Stream;
 
class StreamTest extends ConnectionTest {
 
 	static $classname = 'BlueFission\Connections\Stream';

 	public function setup()
 	{
 		// Set up a bunch of conditions to create an acceptable test connection here
 		parent::setup();
 	}

 	public function testOpen()
    {
        $stream = new Stream();
        $stream->open();

        $this->assertEquals(Stream::STATUS_NOTCONNECTED, $stream->status());

        $stream->config('target', 'http://www.google.com');
        $stream->open();

        $this->assertEquals(Stream::STATUS_CONNECTED, $stream->status());
    }

    public function testQuery()
    {
        $stream = new Stream();
        $stream->open();

        $this->assertEquals(Stream::STATUS_NOTCONNECTED, $stream->status());

        $stream->config('target', 'http://www.google.com');
        $stream->open();
        $stream->query('test');

        $this->assertEquals(Stream::STATUS_FAILED, $stream->status());

        $stream->config('method', 'GET');
        $stream->query();

        $this->assertEquals(Stream::STATUS_SUCCESS, $stream->status());
    }
}