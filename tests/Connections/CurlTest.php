<?php
namespace BlueFission\Tests\Connections;

use BlueFission\Connections\Curl;
 
class CurlTest extends ConnectionTest {
 
 	static $classname = 'BlueFission\Connections\Curl';

 	public function setUp(): void
 	{
 		// Set up a bunch of conditions to create an acceptable test connection here
 		$location = 'https://www.bluefission.com';
 		if ( file_get_contents($location) ) {
 			static::$canbetested = true;
 			static::$configuration['location'] = 'https://www.bluefission.com';
 		}
 		parent::setUp();
 	}
}