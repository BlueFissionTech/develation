<?php
namespace BlueFission\Utils;

use BlueFission\Val;
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
        $message = (Val::isNotNull($message)) ? $message : "If you have recieved this email, then the admnistrative alert system on your website has been activated with no status message. Please check your log files.\n";
        $subject = (Val::isNotNull($subject)) ? $subject : "Automated Email Alert From Your Site!";
        $from = (Val::isNotNull($from)) ? $from : "admin@" . HTTP::domain();
        $rcpt = (Val::isNotNull($rcpt)) ? $rcpt : "admin@" . HTTP::domain();

        $status = Email::sendMail($rcpt, $from, $subject, $message);
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
        $max = (Val::isNotNull($max)) ? $max : 400;
        if ($count >= $max) {
            $status = "Loop exceeded max count! Killing Process.\n";
            if ($alert) Util::emailAdmin($status);
            if ($log) {
                $logger = Log::instance(array('storage'=>'log'));
                $logger->push($status);
                $logger->write();
            }
            if (Val::isNotNull($redirect)) HTTP::redirect($redirect, array('msg'=>$status));
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
		return ( Val::isNotNull($cookie) ) ? $cookie : ( ( Val::isNotNull($post) ) ? $post : $get);
	}
}