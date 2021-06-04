<?php
namespace BlueFission\Services;

use BlueFission\Behavioral\Behaviors\Behavior;
use BlueFission\Behavioral\Configurable;
use BlueFission\Data\IData;
use BlueFission\Net\HTTP;
use BlueFission\Data\Storage\Storage;

class Authenticator extends Configurable {

	protected $_config = [ 
		'session'=>'login',
		'users_table'=>'users',
		'login_attempts_table'=>'login_attempts',
		'credentials_table'=>'credentials',
		'id_field'=>'user_id',
		'username_field'=>'username',
		'password_field'=>'password',
		'lockout_interval'=>10,
		'duration'=>3600,
		'max_attempts'=>10,
	];

	protected $_data = [
		'id'=>'',
		'username'=>'',
		'displayname'=>'',
		'remember'=>'',
	];

	private $_datasource;

	public function __construct( Storage $datasource, $config = null ) {
		parent::__construct($config);
		$this->_datasource = $datasource;
	}

	public function authenticate( $username, $password ) {
		// $users = $this->config('users');
		// $users->

		if (!$this->confirmIPAddress($_SERVER['REMOTE_ADDR']) ) {
			$this->_status[] = 'Too many failures';
			return false;
		}

		if ( "" == $username || "" == $password ) {
			$this->_status[] = "Username and password required";
			return false;
		}
		
		$userinfo = $this->getUser($username);

		if ( !$userinfo ) {
			$this->_status[] = "User not found";
			return false;
		}
		
		$savedpass = $userinfo[$this->config('password_field')];
		$id = $userinfo['user_id'];
		
		// $password = password_hash($password, PASSWORD_DEFAULT);

		if ( !password_verify($password, $savedpass) ) {
			$this->_status[] = "Username or password incorrect";
			return false;
		}

		$this->username = $userinfo[$this->config('username_field')];
		// $this->displayname = $userinfo['displayname'];
		$this->id = $userinfo[$this->config('id_field')];
		
		return true;
	}

	public function isAuthenticated() {
		// return true;
		if ( isset( $_COOKIE[$this->config('session')] ) ) {
			$data = json_decode($_COOKIE[$this->config('session')]);
			$this->assign($data);
		}
		
		if($this->username != '' && $this->id != ''){
			if (!defined("USER_ID")) {
				define("USER_ID", $this->id);
			}
			return true;
		} else {
			return false;
		}
	}

	private function confirmIPAddress($value) 
	{ 
		// $attempts = new Mysql('dash_login_attempts'); // TODO fix this with dependency injection
		$attempts = $this->_datasource;
		$attempts->config('name', $this->config('login_attempts_table'));
		$attempts->activate();
		$last = array();
		$attempts->field('ip_address', $value);
		$attempts->read();
		$last = $attempts->data();

		
		if (isset( $last['last_attempt'] ) && strtotime( $last['last_attempt'] ) > strtotime( $this->config('logout_interval') ) )
		{
			$last['attempts']++;
		}
		else
		{
			$last['attempts'] = 0;
		}
		$attempts->field('last_attempt', date('Y-m-d G:i:s', strtotime('now')));
		$attempts->field('attempts', $last['attempts']);
		$attempts->write();

		if (isset( $last['attempts']) && $last['attempts'] >= $this->config('max_attempts') )
		{
			return false;
		}
		return true;
	}

	private function blockIPAddress() 
	{ 
		// $attempts = new Mysql('dash_login_attempts');
		$attempts = $this->_datasource;
		$attempts->config('name', $this->config('login_attempts_table'));
		$attempts->activate();
		$last = array();
		$attempts->field('ip_address', $_SERVER['REMOTE_ADDR']);
		$attempts->read();
		$last = $attempts->data();

		
		if (isset( $last['last_attempt'] ) && strtotime( $last['last_attempt'] ) > strtotime( $this->config('logout_interval') ) )
		{
			if (isset( $last['attempts']) && $last['attempts'] >= $this->config('max_attempts') )
			{
				return true;
			}
		}
		
		return false;
	}

	private function clearIPAddress() 
	{ 
		// $attempts = new Mysql('dash_login_attempts');
		$attempts = $this->_datasource;
		$attempts->clear();
		$attempts->config('name', $this->config('login_attempts_table'));
		$attempts->activate();
		$last = array();
		$attempts->field('ip_address', $_SERVER['REMOTE_ADDR']);
		$attempts->read();
		$last = $attempts->data();
		
		if (isset( $last['last_attempt'] ) && strtotime( $last['last_attempt'] ) > strtotime( $this->config('logout_interval') ) )
		{
			// $attempts->delete();
			$db->delete('dash_login_attempts', 'ip_address', $_SERVER['REMOTE_ADDR']);
		}
	} 


	public function destroySession() {
		$this->setAuthCookie("", -3600);
		unset($_COOKIE[$this->config('session')]);

		$this->username = '';
		$this->id = 0;

		return true;
	}

	private function getUser($username){
		
		// $user = new Mysql('users');
		$user = $this->_datasource;
		$user->reset();
		$user->clear();
		$user->config('name', [$this->config('credentials_table')]);
		$user->activate();
		$user->field('username', $username);
		$user->read();
		$dbCheck = $user->data();

		if(!empty($dbCheck)){
			return $dbCheck;
		}else{
			return false;
		}
	}
	
	public function setSession() {
		if ( isset( $_COOKIE[$this->config('session')] ) ) {
			if ($this->setAuthCookie(stripslashes($_COOKIE[$this->config('session')])))
				return true;
			else {
				$this->_status[] = "Could not save session";
				return false;
			}
		}

		if ( !$this->isAuthenticated() ) return false;
		$loginData = array(
			'username' => $this->username,
			// 'displayname' => $this->displayname,
			'id' => $this->id,
			'duration' => $this->config('duration')
		);

		$cookie = HTTP::jsonEncode( ($loginData) );

		if ($this->setAuthCookie($cookie))
			return true;
		else {
			$this->_status[] = "Could not save session";
			return false;
		}
	}

	private function getExpiration(){
		return time() + $this->config('duration');
	}
	
	private function setAuthCookie($value, $duration = ""){
		if($duration == ""){
			$duration = $this->config('duration');
		}
		
		$url = parse_url($_SERVER["HTTP_HOST"]);
		$domain = isset($url['host']) ? $url['host'] : null;
		$dir = "/";
		$cookiedie = ($duration > 0) ? time()+(int)$duration : (int)$duration; //expire in one hour
		$cookiesecure = false;
		
		$var = $this->config('session');
		
		return HTTP::cookie($var, $value, $cookiedie, $dir, $cookiesecure);
	}
}