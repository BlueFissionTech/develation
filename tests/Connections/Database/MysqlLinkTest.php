<?php
namespace BlueFission\Tests\Connections;

use BlueFission\Connections\Database\MysqlLink;
 
class MysqlLinkTest extends ConnectionTest {
 
 	static $classname = 'BlueFission\Connections\Database\MysqlLink';

 	public function setup()
 	{
 		// Set up a bunch of conditions to create an acceptable test connection here
 		if ( function_exists('mysql_connect') )
 			static::$canbetested = true;
 		parent::setup();
 	}
}