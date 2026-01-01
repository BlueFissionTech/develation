<?php
namespace BlueFission\Tests\Connections;

use BlueFission\Connections\Curl;
use BlueFission\Net\HTTP;
use BlueFission\Tests\Support\TestEnvironment;

require_once __DIR__ . '/../Support/TestEnvironment.php';

class CurlTest extends ConnectionTest {
 
 	static $classname = 'BlueFission\Connections\Curl';

 	public function setUp(): void
 	{
 		if (!TestEnvironment::isNetworkEnabled()) {
 			$this->markTestSkipped('Network tests are disabled');
 		}

 		$location = getenv('DEV_ELATION_CURL_TEST_URL') ?: 'https://www.bluefission.com';
 		if (!HTTP::urlExists($location)) {
 			$this->markTestSkipped('Curl target is not reachable');
 		}

 		static::$canbetested = true;
 		static::$configuration = ['target' => $location];

 		parent::setUp();
 	}
}
