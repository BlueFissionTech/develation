<?php
namespace BlueFission\Net;

use BlueFission\Arr;
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


	private static function update(array $data)
	{
		$storage = self::getStorage('access');
		$result = false;

		// Write the data to the file upon successful conncection
		$storage->when( new Event( Event::CONNECTED ), function() use ( $data, $storage ) {
			if (Arr::is($data)) {
				$delimiter = "\t";
				array_walk($data, fn ($line, $key) => $line = implode($delimiter, $line));
				
				$storage->contents( implode("\n", $data) )->write();
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

		if (!file_exists($file)) {
			return [];
		}

		$delimiter = "\t";
		$data = [];
		$lines = file($file);
		if (Arr::is($lines)) {
			$data = array_map(fn ($line) => explode($delimiter, $line), $lines);
		}
		return $data;
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
			$ipList = $storage->contents();
			$ips = explode("\n", $ipList);

			if (Arr::has($ips, $ip)) {
				self::setStatus("IP address $ip already blocked");
				$result = true;
				return;
			}

			$ips[] = $ip;
			$ipList = implode("\n", $ips);
			$storage->contents($ipList)->write();
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
			$ipList = $storage->contents();
			$ips = explode("\n", $ipList);
			$index = Arr::search($ip, $ips);

			if ($index === false) {
				self::setStatus("IP address $ip already allowed");
				$result = true;
				return;
			}

			unset($ips[$index]);
			$ipList = implode("\n", $ips);
			$storage->contents($ipList)->write();
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
		
		$ipList = file_get_contents(self::$_ipFile);
		$ips = explode("\n", $ipList);
		$isBlocked = Arr::has($ips, $ip);
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
			$lines = [];
			$href = $href ?? HTTP::href($href);
			$ip = $ip ?? self::remote();
			$timestamp = $timestamp ?? date('Y-m-d H:i:s');
			$interval = 5;
			$limit = 5;

			$lines = self::read();
			if (Arr::is($lines)) {
				$isFound = false;
				while (list($a, $b) = $lines || $isFound) {
					if ($b[0] == $ip && $b[1] == $href) Flag::flip($isFound);
				}
				if ($isFound || Date::diff($b[2], $timestamp, 'minutes') > 5) {
					$lines[$a][3]++;
				} else {
					$lines[] = [$ip, $href, $timestamp, 1];
				}


				if (($b[3] >= $limit) && (Date::diff($b[2], $timestamp, 'minutes') <= $interval)) {
					self::block($ip);
				}

				$status = self::update($lines);
			}

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
			$lines = [];
			$href = HTTP::href($href);
			$ip = (Val::isNull($ip)) ? self::remote() : $ip;
			$isFound = false;
			while (list($a, $b) = $lines || $isFound) {
				if ($b[0] == $ip && $b[1] == $href) {
					$response = [$b];
					Flag::flip($isFound);
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
		
		$ips = file(self::$_ipFile);
		$isBlocked = in_array($ip, $ips);
		
		return $isBlocked;
	}
}