<?php
namespace BlueFission\Net;

use BlueFission\Arr;
use BlueFission\Str;
use BlueFission\Val;
use BlueFission\Date;
use BlueFission\Flag;
use BlueFission\Net\HTTP;
use BlueFission\Data\FileSystem;
use BlueFission\Data\IData;
use BlueFission\Behavioral\Behaviors\Event;

/**
 * Class IP
 * 
 * The IP class provides functionality to retrieve the remote IP address and handle
 * IP blocking, allowing, logging and querying log.
 *
 * @package BlueFission\Net
 */
class IP {

	private static $_accessLog = 'access_log.txt';
	private static $_ipFile = 'blocked_ips.txt';
	private static $_storage = null;
	private static $_status = "";

	private static function setStatus($status)
	{
		self::$_status = $status;
	}

	public static function status()
	{
		return self::$_status;
	}

	public static function storage(IData $storage = null)
	{
		if (Val::isNull($storage)) {
			return self::$_storage;
		}

		self::$_storage = $storage;
	}

	private static function getStorage( $type = null )
	{
		if (Val::isNull(self::$_storage)) {
			$file = $type == 'ip' ? self::$_ipFile : self::$_accessLog;

			return (new FileSystem($file))->config('mode', 'rw');
		}

		return self::$_storage;
	}

	public static function accessLog($file = null)
	{
		if (Val::isNull($file)) {
			return self::$_accessLog;
		}

		self::$_accessLog = $file;
	}

	public static function ipFile($file = null)
	{
		if (Val::isNull($file)) {
			return self::$_ipFile;
		}

		self::$_ipFile = $file;
	}

	private static function ipLines($ipList): Arr
	{
		return Str::make((string)$ipList)
			->split("\n")
			->map(fn ($line) => Str::trim($line))
			->filter(fn ($line) => Str::isNotEmpty($line))
			->values();
	}

	private static function serializeLines($lines, string $delimiter = "\n"): string
	{
		return Arr::make($lines)->join($delimiter)->val();
	}

	private static function update(array $data)
	{
		$storage = self::getStorage('access');
		$result = false;

		// Write the data to the file upon successful conncection
		$storage->when( new Event( Event::CONNECTED ), function() use ( $data, $storage ) {
			if (Arr::is($data)) {
				$delimiter = "\t";
				$lines = Arr::make($data)
					->map(fn ($line) => self::serializeLines($line, $delimiter))
					->values();
				$storage->contents(self::serializeLines($lines))->write();
			}
		})

		// If the save is successful, set the status
		->when( new Event( Event::SAVED ), function() use( &$result ) {
			self::setStatus("IP logging successful");
			$result = true;
		})
		
		// If the save fails, set the status
		->when( new Event( Event::FAILURE ), function() {
			self::setStatus("IP logging failed");
		})
		
		// If an error occurs, set the status
		->when( new Event( Event::ERROR ), function() {
			self::setStatus("IP logging failed");
		})
		
		// Open the file
		->open();

		return $result;
	}

	private static function read()
	{
		$file = self::$_accessLog;

		if (!FileSystem::fileExists($file)) {
			return [];
		}

		$delimiter = "\t";
		$contents = FileSystem::fileContents($file);
		if (Val::isEmpty($contents)) {
			return [];
		}

		return Str::make($contents)
			->split("\n")
			->filter(fn ($line) => Str::isNotEmpty(Str::trim($line)))
			->map(fn ($line) => Str::make($line)->split($delimiter)->val())
			->values()
			->val();
	}


	/**
	 * Retrieve the remote IP address of the client.
	 * 
	 * @return string The remote IP address
	 */
	public static function remote() {
		return $_SERVER['REMOTE_ADDR'] ?? null;
	}

	/**
	 * Block an IP address
	 * 
	 * @param string $ip         The IP address to be blocked
	 * @param string $_ipFile    (Optional) File to store the blocked IP addresses
	 * 
	 * @return string The status of the IP blocking process
	 */
	public static function deny($ip) {
		$storage = self::getStorage('ip');
		$result = false;

		// Write the data to the file upon successful conncection
		$storage->when( Event::CONNECTED, function() use ( $storage ) {
			$storage->read();
		})

		// Write the data to the file upon successful conncection
		->when( Event::READ , function() use ( &$result, $storage, $ip ) {
			$ipList = (string)$storage->contents();
			$ips = self::ipLines($ipList);

			if ($ips->has($ip)) {
				self::setStatus("IP address $ip already blocked");
				$result = true;
				return;
			}

			$ips[] = $ip;
			$storage->contents(self::serializeLines($ips))->write();
		})

		// If the save is successful, set the status
		->when( Event::SAVED , function() use( &$result, $ip ) {
			self::setStatus("Blocked IP address $ip");
			$result = true;
		})

		// Errors		
		->when( Event::FAILURE, fn() => self::setStatus("IP blocking failed for $ip") )
		->when( Event::ERROR, fn() => self::setStatus("IP blocking error for $ip") )
		
		// Open the file
		->open();

		return $result;
	}

	/**
	 * Allow an IP address that was previously blocked
	 * 
	 * @param string $ip         The IP address to be allowed
	 * @param string $_ipFile    (Optional) File to store the blocked IP addresses
	 * 
	 * @return string The status of the IP allowing process
	 */
	public static function allow($ip)
	{
		$storage = self::getStorage('ip');
		$result = false;

		// Write the data to the file upon successful conncection
		$storage->when( Event::CONNECTED, function() use ( $storage ) {
			$storage->read();
		})

		// Write the data to the file upon successful conncection
		->when( Event::READ , function() use ( &$result, $storage, $ip ) {
			$ips = self::ipLines($storage->contents());
			$index = $ips->search($ip);

			if ($index === false) {
				self::setStatus("IP address $ip already allowed");
				$result = true;
				return;
			}

			unset($ips[$index]);
			$storage->contents(self::serializeLines($ips->values()))->write();
		})

		// If the save is successful, set the status
		->when( Event::SAVED , function() use( &$result, $ip ) {
			self::setStatus("Blocked IP address $ip");
			$result = true;
		})

		// Errors		
		->when( Event::FAILURE, fn() => self::setStatus("IP allowing failed for $ip") )
		->when( Event::ERROR, fn() => self::setStatus("IP allowing error for $ip") )
		
		// Open the file
		->open();

		return $result;
	}

	/**
	 * Handle IP restrictions
	 * 
	 * Check if an IP is blocked and redirects to a specified URL or
	 * exits with a message.
	 * 
	 * @param string $ip        (Optional) The IP address to handle
	 * @param string $redirect  (Optional) URL to redirect to
	 * @param bool   $exit      (Optional) Whether to exit after handling IP restriction
	 * 
	 * @return string The status of the IP handling process
	 */
	public static function handle($ip = '', $redirect = '', $exit = false) {
		$isBlocked = false;
		$status = "IP Allowed";
		self::setStatus($status);
		
		$ip = ($ip == '') ? self::remote() : $ip;
		
		$ipList = FileSystem::fileContents(self::$_ipFile);
		$isBlocked = self::ipLines($ipList)->has($ip);
		if ($isBlocked) {
			$status = "Your IP address has been restricted from viewing this content. Please contact the administrator.";
			if ($exit) exit($status);
			if ($redirect != '') HTTP::redirect($redirect);
			self::setStatus($status);
			return false;
		}

		return true;
	}

	/**
	 * Logs a file with the given IP address, href, and timestamp.
	 *
	 * @param string $file The file to be logged.
	 * @param string $href The href of the log.
	 * @param string $ip The IP address of the log.
	 *
	 * @return string The status of the log, either success or a message indicating failure.
	 */
	public static function log($ip = null, $href = null, $timestamp = null) 
	{
			$href = $href ?? HTTP::href($href);
			$ip = $ip ?? self::remote();
			$timestamp = $timestamp ?? date('Y-m-d H:i:s');
			$interval = 5;
			$limit = 5;

			$lines = self::read();
			if (!Arr::is($lines)) {
				$lines = [];
			}

			$foundIndex = null;
			foreach ($lines as $index => $entry) {
				if (($entry[0] ?? null) === $ip && ($entry[1] ?? null) === $href) {
					$foundIndex = $index;
					break;
				}
			}

			if ($foundIndex !== null) {
				$entry = $lines[$foundIndex];
				$lastTimestamp = $entry[2] ?? $timestamp;
				$count = (int)($entry[3] ?? 0);

				if (Date::diff($lastTimestamp, $timestamp, 'minutes') <= $interval) {
					$count++;
				} else {
					$count = 1;
				}

				$lines[$foundIndex] = [$ip, $href, $timestamp, $count];

				if ($count >= $limit && Date::diff($lastTimestamp, $timestamp, 'minutes') <= $interval) {
					self::block($ip);
				}
			} else {
				$lines[] = [$ip, $href, $timestamp, 1];
			}

			self::update($lines);

			return true;
		}

	/**
	 * Queries a log file for a specific IP address, href, and time interval.
	 *
	 * @param string $file The log file to be queried.
	 * @param string $href The href of the log.
	 * @param string $ip The IP address of the log.
	 * @param int $limit The limit for the number of logs.
	 * @param int $interval The time interval for the logs.
	 *
	 * @return string The status of the query, either success or a message indicating failure.
	 */
	public static function query($href = null, $ip = null) {
		$lines = self::read();
		if (Arr::is($lines)) {
			$href = HTTP::href($href);
			$ip = (Val::isNull($ip)) ? self::remote() : $ip;
			$response = [];
			foreach ($lines as $entry) {
				if (($entry[0] ?? null) === $ip && ($entry[1] ?? null) === $href) {
					$response[] = $entry;
				}
			}
		} else {
			$response = $lines;
		}
		
		return $response;
	}

	public static function block($ip)
	{
		$status = "Blocking IP address $ip";
		$result = file_put_contents(self::$_ipFile, $ip . "\n", FILE_APPEND | LOCK_EX);
		$status = ($result ? "IP Block Successful" : "IP Block Failed") . "for $ip";

		self::setStatus($status);

		return $result;
	}

	public static function isDenied($ip)
	{
		$isBlocked = false;

		$ip = $ip ?? self::remote();

		$isBlocked = self::ipLines(FileSystem::fileContents(self::$_ipFile))->has($ip);

		return $isBlocked;
	}
}
