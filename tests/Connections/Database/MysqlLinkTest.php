<?php
namespace BlueFission\Tests\Connections\Database;

use BlueFission\Tests\Connections\ConnectionTest;
use BlueFission\Connections\Connection;
use BlueFission\Connections\Database\MySQLLink;
use BlueFission\Net\HTTP;
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

	public function testConcurrentConflictingInsertsHaveOneObservableWinner(): void
	{
		$table = 'develation_link_' . bin2hex(random_bytes(6));
		$this->object->open();
		$this->object->query(
			"CREATE TABLE `{$table}` ("
			. '`id` INT UNSIGNED NOT NULL AUTO_INCREMENT, '
			. '`event_key` VARCHAR(96) NOT NULL, '
			. '`payload` LONGTEXT NOT NULL, '
			. 'PRIMARY KEY (`id`), UNIQUE KEY `event_key` (`event_key`)'
			. ') ENGINE=InnoDB'
		);

		try {
			$results = $this->runConcurrentInserts($table, [
				'{"alpha":1,"beta":2}',
				'{"beta":2,"alpha":1}',
			]);

			$successes = array_filter($results, fn (array $result) => $result['result'] === true);
			$failures = array_filter($results, fn (array $result) => $result['result'] === false);

			$this->assertCount(1, $successes);
			$this->assertCount(1, $failures);
			$this->assertSame(Connection::STATUS_SUCCESS, array_values($successes)[0]['status']);
			$this->assertNotSame(Connection::STATUS_SUCCESS, array_values($failures)[0]['status']);
			$this->assertStringContainsString('Duplicate entry', array_values($failures)[0]['status']);
		} finally {
			$this->object->query("DROP TABLE IF EXISTS `{$table}`");
		}
	}

	private function runConcurrentInserts(string $table, array $payloads): array
	{
		$startAt = microtime(true) + 0.5;
		$processes = [];

		foreach ($payloads as $payload) {
			$config = static::$configuration + [
				'table' => $table,
				'key' => 'id',
			];
			$input = HTTP::jsonEncode([
				'config' => $config,
				'data' => [
					'event_key' => 'shared-event',
					'payload' => $payload,
				],
				'start_at' => $startAt,
			]);
			$pipes = [];
			$process = proc_open(
				[PHP_BINARY, dirname(__DIR__, 2) . '/Support/mysql_insert_worker.php', $input],
				[1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
				$pipes,
				dirname(__DIR__, 3)
			);

			$this->assertIsResource($process);
			$processes[] = [$process, $pipes];
		}

		return array_map(function (array $worker): array {
			[$process, $pipes] = $worker;
			$output = stream_get_contents($pipes[1]);
			$error = stream_get_contents($pipes[2]);
			fclose($pipes[1]);
			fclose($pipes[2]);
			$status = proc_close($process);

			$this->assertSame(0, $status, $error);

			return HTTP::jsonDecode($output, true, []);
		}, $processes);
	}
}
