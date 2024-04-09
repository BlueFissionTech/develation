<?php
namespace BlueFission\Net;

use BlueFission\Arr;
use BlueFission\Val;
use BlueFission\Date;
use BlueFission\Flag;
use BlueFission\Net\HTTP;
use BlueFission\Data\File;
use BlueFission\Data\IData;


/**
 * Class IP
 * 
 * The IP class provides functionality to retrieve the remote IP address and handle
 * IP blocking, allowing, logging and querying log.
 *
 * @package BlueFission\Net
 */
class IP {

	private static $accessLog = 'access_log.txt';
	private static $ipFile = 'blocked_ips.txt';

	public static function accessLog($file = null)
	{
		if (Val::isNull($file)) {
			return self::$accessLog;
		}

		self::$accessLog = $file;
	}

	public static function ipFile($file = null)
	{
		if (Val::isNull($file)) {
			return self::$ipFile;
		}

		self::$ipFile = $file;
	}


	private static function update($data)
	{
		$file = self::$accessLog;

		if (!file_exists($file)) {
			$handle = fopen($file, 'w');
			if (!$handle) {
				return "Failed to create file.";
			}
			fclose($handle);
		}

		if (Arr::is($data)) {
			$delimiter = "\t";
			array_walk($data, fn ($line, $key) => $line = implode($delimiter, $line));
			$status = file_put_contents($file, implode("\n", $data), LOCK_EX) 
				? "Data updated successfully." : "Failed to update data.";
		} else {
			$status = "Data not valid. Argument requires array.";
		}
		return $status;
	}

	private static function read()
	{
		$file = self::$accessLog;

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
		return $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * Block an IP address
	 * 
	 * @param string $ip         The IP address to be blocked
	 * @param string $ipFile    (Optional) File to store the blocked IP addresses
	 * 
	 * @return string The status of the IP blocking process
	 */
	public static function deny($ip) {
		$status = "Blocking IP address $ip.\n";
		// Add IP address to block file
		$ipList = file_get_contents(self::$ipFile);
		$ips = explode("\n", $ipList);
		if (!Arr::has($ips, $ip)) {
			$ips[] = $ip;
			$ipList = implode("\n", $ips);
			$status = file_put_contents(self::$ipFile, $ipList, LOCK_EX);
		} else {
			$status = "IP is already blocked\n";
		}

		return $status;
	}

	/**
	 * Allow an IP address that was previously blocked
	 * 
	 * @param string $ip         The IP address to be allowed
	 * @param string $ipFile    (Optional) File to store the blocked IP addresses
	 * 
	 * @return string The status of the IP allowing process
	 */
	public static function allow($ip)
	{
		$status = "IP Allow Failed";
		$ipList = file_get_contents(self::$ipFile);
		$ips = explode("\n", $ipList);
		$index = Arr::search($ip, $ips);
		if ($index !== false) {
			unset($ips[$index]);
			$ipList = implode("\n", $ips);
			$status = file_put_contents(self::$ipFile, $ipList, 'w');
		} else {
			$status = "IP is already not blocked";
		}
		return $status;
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
		$status = '';
		
		$ip = ($ip == '') ? self::remote() : $ip;
		
		$ipList = file_get_contents(self::$ipFile);
		$ips = explode("\n", $ipList);
		$isBlocked = Arr::has($ips, $ip);
		if ($isBlocked) {
			$status = "Your IP address has been restricted from viewing this content.\nPlease contact the administrator.\n";
			if ($exit) exit($status);
			if ($redirect != '') HTTP::redirect($redirect);
		}
		
		return $status;
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
					if ($b[0] == $ip && $b[1] == $href) Flag::opposite($isFound);
				}
				if ($isFound || Date::difference($b[2], $timestamp, 'minutes') > 5) {
					$lines[$a][3]++;
				} else {
					$lines[] = [$ip, $href, $timestamp, 1];
				}


				if (($b[3] >= $limit) && (Date::difference($b[2], $timestamp, 'minutes') <= $interval)) {
					self::block($ip);
				}

				$status = self::update($lines);
			}

			return $status;
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
					Flag::opposite($isFound);
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
		file_put_contents(self::$ipFile, $ip . "\n", FILE_APPEND | LOCK_EX);
		return $status;
	}

	public static function isDenied($ip)
	{
		$isBlocked = false;
		
		$ip = $ip ?? self::remote();
		
		$ips = file(self::$ipFile);
		$isBlocked = in_array($ip, $ips);
		
		return $isBlocked;
	}
}