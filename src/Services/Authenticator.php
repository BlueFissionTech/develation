<?php

class Authenticator extends Configurable {
	protected $_config( 
		'session'=>'',
		'users'=>'',
		'crendentials'=>''
	);

	public function __construct() {
		
	}

	private function authenticate( Behavior $event = null ) {
		$users = $this->config('users');
		$users->
	}
}