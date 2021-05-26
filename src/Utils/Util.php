<?php
namespace BlueFission\Utils;

use BlueFission\DevValue;
use BlueFission\Net\Email;
use BlueFission\Net\HTTP;

class Util {
	static function emailAdmin($message = '', $subject = '', $from = '', $rcpt = '') {
		$message = (DevValue::isNotNull($message)) ? $message : "If you have recieved this email, then the admnistrative alert system on your website has been activated with no status message. Please check your log files.\n";
		$subject = (DevValue::isNotNull($subject)) ? $subject : "Automated Email Alert From Your Site!";
		$from = (DevValue::isNotNull($from)) ? $from : "admin@" . HTTP::domain();
		$rcpt = (DevValue::isNotNull($rcpt)) ? $rcpt : "admin@" . HTTP::domain();
		
		$email = Email($rcpt, $from, $subject, $message);
		$status = $email->send();
		return $status;
	}

	static function parachute(&$count, $max = '', $redirect = '', $log = false, $alert = false) {
		$max = (DevValue::isNotNull($max)) ? $max : 400;
		if ($count >= $max) {
			$status = "Loop exceeded max count! Killing Process.\n";
			if ($alert) Util::emailAdmin($status);
			if ($log) {
				$logger = Log::instance(array('storage'=>'log'));
				$logger->push($status);
				$logger->write();
			}
			if (DevValue::isNotNull($redirect)) HTTP::redirect($redirect, array('msg'=>$status));
			else exit("A script on this page began to loop out of control. Process has been killed. If you are viewing this message, please alert the administrator.\n");
		}
		$count++;
	}

	static function value($var) {
		$cookie = ( array_key_exists( $var, $_COOKIE) ) ? $_COOKIE[$var] : NULL;  
		$get = ( array_key_exists( $var, $_GET) ) ? $_GET[$var] : NULL;
		$post = ( array_key_exists( $var, $_POST) ) ? $_POST[$var] : NULL;
		return ( DevValue::isNotNull($cookie) ) ? $cookie : (( DevValue::isNotNull($post) ) ? $post : $get);
	}
}