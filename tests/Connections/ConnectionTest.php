<?php
namespace BlueFission\Tests\Connections;

use BlueFission\Connections\Connection;
 
abstract class ConnectionTest extends \PHPUnit_Framework_TestCase {
 
 	static $classname = 'BlueFission\Connections\Connection';
 	static $canbetested = false;
 	static $configuration = array();
	
	public function setup()
	{
		$this->object = new static::$classname();
	}

	public function testDefaultStatusIsNotConnected()
	{
		if ( !static::$canbetested ) return;
		$this->assertEquals(Connection::STATUS_NOTCONNECTED, $this->object->status() );
	}

	public function testCorrectionStatusOnSuccessfulOpen()
	{
		if ( !static::$canbetested ) return;
		$this->object->open();

		$this->assertEquals(Connection::STATUS_CONNECTED, $this->object->status() );
	}
}