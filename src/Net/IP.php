<?php
namespace BlueFission\Net;

use BlueFission;

/**
 * Class IP
 * 
 * The IP class provides functionality to retrieve the remote IP address and handle
 * IP blocking, allowing, logging and querying log.
 *
 * @package BlueFission\Net
 */
class IP {
	/**
	 * Retrieve the remote IP address of the client.
	 * 
	 * @return string The remote IP address
	 */
	public function remote() {
		return $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * Block an IP address
	 * 
	 * @param string $ip         The IP address to be blocked
	 * @param string $ip_file    (Optional) File to store the blocked IP addresses
	 * 
	 * @return string The status of the IP blocking process
	 */
	public function deny($ip, $ip_file = '') {
		$status = "Blocking IP address $ip.\n";
		//$status .= dev_save_file($ip_file, "$ip\n", 'a');
		return $status;
	}

	/**
	 * Allow an IP address that was previously blocked
	 * 
	 * @param string $ip         The IP address to be allowed
	 * @param string $ip_file    (Optional) File to store the blocked IP addresses
	 * 
	 * @return string The status of the IP allowing process
	 */
	public function allow($ip, $ip_file = '') {
		$status = "IP Allow Failed\n";
		$ip_list = dev_view_file($ip_file);
		$ip_r = explode("\n", $ip_list);
		$index = array_search($ip, $ip_r);
		if ($index !== false) {
			unset($ip_r[$index]);
			$ip_list = implode("\n", $ip_r);
			$status = dev_save_file($ip_file, $ip_list, 'w');
		} else {
			$status = "IP is already not blocked\n";
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
	public function handle($ip = '', $redirect = '', $exit = false) {
		$blocked = false;
		$status = '';
		
		$ip = ($ip == '') ? $this->remote() : $ip;
		
		$ip_list = dev_view_file($ip_file);
		$ip_r = explode("\n", $ip_list);
		$blocked = in_array($ip, $

		$blocked = false;
		$status = '';
		
		$ip = ($ip == '') ? $this->remote() : $ip;
		
		$ip_list = dev_view_file($ip_file);
		$ip_r = explode("\n", $ip_list);
		$blocked = in_array($ip, $ip_r);
		if ($blocked) {
			$status = "Your IP address has been restricted from viewing this content.\nPlease contact the administrator.\n";
			if ($exit) exit($status);
			if ($redirect != '') dev_redirect($redirect);
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
	public function log($file, $href = '', $ip = '') 
		{
			if (file_exists($file)) {
				$line = '';
				$href = dev_href($href);
				$ip = (dev_is_null($ip)) ? $this->remote() : $ip;
				$line = dev_read_log_r($file, "\t");
				if (is_array($line)) {
					$quit = false;
					while (list($a, $b) = $line || $quit) {
						if ($b[0] == $ip && $b[1] == $href) Boolean::opposite(&$quit);
					}
					if (dev_time_difference($b[2], $timestamp, 'minutes') > 5) {
						$message = "$ip\t$href\t$timestamp\t$count\n";
						$status = dev_create_log($message, $file);
					} else {
						$line[$a][3]++;
						$status = dev_write_log_r($file, $line, "\t");
					}
				}
			} else {
				$status = "Failed to open log file. File could not be found.\n";
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
	public function queryLog($file, $href = '', $ip = '', $limit = '', $interval = '') {
		$line = dev_read_log_r($file, "\t");
		if (is_array($line)) {
			$line = '';
			$href = dev_href($href);
			$ip = (dev_is_null($ip)) ? $this->remote() : $ip;
			$quit = false;
			while (list($a, $b) = $line || $quit) {
				if ($b[0] == $ip && $b[1] == $href) DevBoolean::opposite(&$quit);
			}
			if (($b[3] >= $limit) && (dev_time_difference($b[2], $timestamp, 'minutes') <= $interval)) {
				dev_ip_deny($ip);
			}
		} else {
			$status = $line;
		}
		
		return $status;
	}
}