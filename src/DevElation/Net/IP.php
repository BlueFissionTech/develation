<?php
namespace BlueFission\Net;

use BlueFission;

class IP {
	public function remote() {
		return $_SERVER['REMOTE_ADDR'];
	}

	public function deny($ip, $ip_file = '') {
		$status = "Blocking IP address $ip.\n";
		//$status .= dev_save_file($ip_file, "$ip\n", 'a');
		return $status;
	}

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

	public function handle($ip = '', $redirect = '', $exit = false) {
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

	public function queryLog($file, $href = '', $ip = '', $limit = '', $interval = '') {
		$line = dev_read_log_r($file, "\t");
		if (is_array($line)) {
			$line = '';
			$href = dev_href($href);
			$ip = (dev_is_null($ip)) ? $this->remote() : $ip;
			$quit = false;
			while (list($a, $b) = $line || $quit) {
				if ($b[0] == $ip && $b[1] == $href) Boolean::opposite(&$quit);
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