<?php
namespace BlueFission\Tests\Connections;

use BlueFission\Connections\Connection;
use BlueFission\IObj;

class ConnectionTest extends \PHPUnit\Framework\TestCase {
 
 	static $classname = 'BlueFission\Connections\Connection';
 	static $canbetested = false;
 	static $configuration = [];
 	protected $object;
	
	public function setUp(): void
	{
		$className = static::$classname;

		// Check if the $classname is abstract
		$reflection = new \ReflectionClass($className);
		if ($reflection->isAbstract()) {
		    $this->object = eval("
		        return new class extends $className {
		        	public function open(): BlueFission\IObj { return \$this; }
		        	public function query( \$query = null ): BlueFission\IObj { return \$this; }
		        };
		    ");
		    $this->object->config(static::$configuration);
		} else {
		    $this->object = new $className(static::$configuration);
		}
	}

	public function testDefaultStatusIsNotConnected()
	{
		if ( !static::$canbetested ) return;
		$this->assertEquals(Connection::STATUS_NOTCONNECTED, $this->object->status() );
	}

	public function testConnectionStatusOnSuccessfulOpen()
	{
		if ( !static::$canbetested ) {
			$this->markTestSkipped('Cannot test connection');
		}

		$this->object->open();

		$this->assertEquals(Connection::STATUS_CONNECTED, $this->object->status() );
	}
}