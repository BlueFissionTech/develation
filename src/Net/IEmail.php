<?php
namespace BlueFission\Net;

interface IEmail {
	public function subject( $subject = null);
	public function message( $message = null);
	public function recipients( $email = null, $name = null, $type = null);
	public function from( $email = null, $name = null);

	public function send();
	public function status($status = null);
}