<?php
namespace BlueFission\Tests\Connections;

use BlueFission\Connections\Curl;
 
class CurlTest extends ConnectionTest {
 
 	static $classname = 'BlueFission\Connections\Curl';

 	public function setup()
 	{
 		// Set up a bunch of conditions to create an acceptable test connection here
 		$location = 'http://www.google.com';
 		if ( file_get_contents($location) ) {
 			static::$canbetested = true;
 			static::$configuration['location'] = 'http://www.google.com';
 		}
 		parent::setup();
 	}
}