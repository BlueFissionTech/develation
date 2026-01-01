<?php
namespace BlueFission\Tests\Connections\Database;

use BlueFission\Tests\Connections\ConnectionTest;
use BlueFission\Connections\Database\MySQLLink;
use BlueFission\Tests\Support\TestEnvironment;

require_once __DIR__ . '/../../Support/TestEnvironment.php';
 
class MySQLLinkTest extends ConnectionTest {
 
 	static $classname = 'BlueFission\Connections\Database\MySQLLink';

 	public function setUp(): void
 	{
 		$config = TestEnvironment::mysqlConfig();
 		if (!class_exists('mysqli') || !$config) {
 			$this->markTestSkipped('MySQL tests require mysqli and DEV_ELATION_MYSQL_* env vars');
 		}

 		static::$canbetested = true;
 		static::$configuration = [
 			'target' => $config['host'],
 			'username' => $config['user'],
 			'password' => $config['pass'],
 			'database' => $config['db'],
 			'port' => $config['port'],
 		];

 		parent::setUp();
 	}
}
