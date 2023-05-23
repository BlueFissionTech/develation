<?php
namespace BlueFission\Utils;

use BlueFission\DevValue;
use BlueFission\Net\Email;
use BlueFission\Net\HTTP;

class Util {
    /**
     * sends an email to the admin with a specified message, subject, from and recipient
     *
     * @param string $message
     * @param string $subject
     * @param string $from
     * @param string $rcpt
     * @return bool
     */
    static function emailAdmin($message = '', $subject = '', $from = '', $rcpt = '') {
        $message = (DevValue::isNotNull($message)) ? $message : "If you have recieved this email, then the admnistrative alert system on your website has been activated with no status message. Please check your log files.\n";
        $subject = (DevValue::isNotNull($subject)) ? $subject : "Automated Email Alert From Your Site!";
        $from = (DevValue::isNotNull($from)) ? $from : "admin@" . HTTP::domain();
        $rcpt = (DevValue::isNotNull($rcpt)) ? $rcpt : "admin@" . HTTP::domain();

        $email = Email::send($rcpt, $from, $subject, $message);
        $status = $email->send();
        return $status;
    }

    /**
     * check if count is greater than max and then either redirect, email or exit
     *
     * @param int $count
     * @param int $max
     * @param string $redirect
     * @param bool $log
     * @param bool $alert
     */
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

    /**
     * generates a csrf token
     *
     * @return string
     */
    static function csrf_token()
    {
        $token = bin2hex(random_bytes(32));

        return $token;
    }

    /**
     * get a value from a cookie, post or get
     *
     * @param string $var
     * @param int $filter
     * @return mixed
     */
    static function value($var, $filter = FILTER_DEFAULT ) {

        $cookie = filter_input(INPUT_COOKIE, $var);
		$get = filter_input(INPUT_GET, $var);
		$post = filter_input(INPUT_POST, $var);
		return ( DevValue::isNotNull($cookie) ) ? $cookie : ( ( DevValue::isNotNull($post) ) ? $post : $get);
	}
}