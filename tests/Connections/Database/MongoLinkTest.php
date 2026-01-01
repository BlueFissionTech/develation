<?php
namespace BlueFission\Tests\Connections\Database;

use BlueFission\Tests\Connections\ConnectionTest;
use BlueFission\Connections\Database\MongoLink;
use BlueFission\Tests\Support\TestEnvironment;

require_once __DIR__ . '/../../Support/TestEnvironment.php';
 
class MongoLinkTest extends ConnectionTest {
 
 	static $classname = 'BlueFission\Connections\Database\MongoLink';

 	public function setUp(): void
 	{
 		$config = TestEnvironment::mongoConfig();
 		if (!class_exists('MongoDB\\Client') || !$config) {
 			$this->markTestSkipped('Mongo tests require mongodb extension and DEV_ELATION_MONGO_URI');
 		}

 		static::$canbetested = true;
 		static::$configuration = [
 			'target' => $config['host'],
 			'username' => $config['user'],
 			'password' => $config['pass'],
 			'database' => $config['db'],
 		];

 		parent::setUp();
 	}
}
