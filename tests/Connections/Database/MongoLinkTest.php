<?php
namespace BlueFission\Tests\Connections;

use BlueFission\Connections\Database\MongoLink;
 
class MongoLinkTest extends ConnectionTest {
 
 	static $classname = 'BlueFission\Connections\Database\MongoLink';

 	public function setup()
 	{
 		// Set up a bunch of conditions to create an acceptable test connection here
 		if ( class_exists('\MongoDB\Client'))
 			static::$canbetested = true;
 		parent::setup();
 	}
}