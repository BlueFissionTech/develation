<?php
namespace BlueFission\Services;

use BlueFission\Behavioral\Behaviors\Behavior;
use BlueFission\Behavioral\Configurable;
use BlueFission\Data\IData;
use BlueFission\Data\Storage\Mysql;

class Authenticator extends Configurable {

	protected $_config = [ 
		'session'=>'',
		'users'=>'',
		'credentials'=>''
	];

	private $_datasource;

	public function __construct( IData $datasource ) {
		parent::__construct();
		$this->_datasource = $datasource;
	}

	private function authenticate( Behavior $event = null ) {
		// $users = $this->config('users');
		// $users->

		if (!$this->confirmIPAddress($_SERVER['REMOTE_ADDR']) ) {
			$this->status = 'Too many failures';
			return false;
		}

		if ( "" == $username || "" == $password ) {
			$this->status = "Username and password required";
			return false;
		}
		
		$userinfo = $this->getUser($username);

		if ( !$userinfo ) {
			$this->status = "User not found";
			return false;
		}
		
		$savedpass = $userinfo[$this->passwordField];
		$id = $userinfo['user_id'];
		
		$password = $this->hashPassword( $password, $id );

		if ( $password != $savedpass ) {
			$this->status = "Username or password incorrect";
			return false;
		}

		$this->username = $userinfo[$this->usernameField];
		$this->realname = $userinfo['realname'];
		$this->userID = $userinfo['user_id'];
		
		return true;
	}

	public function isAuthenticated() {
		if($this->username !== false && $this->realname !== false && $this->userID !== false){
			define("USER_ID", $this->id);
			return true;
		} else {
			return false;
		}
	}

	private function confirmIPAddress($value) 
	{ 
		$attempts = new Mysql('dash_login_attempts'); // TODO fix this with dependency injection
		$last = array();
		$attempts->setField('ip_address', $value);
		$last = $attempts->read();
		
		if (isset( $last['last_attempt'] ) && strtotime( $last['last_attempt'] ) > strtotime( LOCKOUT_INTERVAL ) )
		{
			$last['attempts']++;
		}
		else
		{
			$last['attempts'] = 0;
		}
		$attempts->field('last_attempt', date('Y-m-d G:i:s', strtotime('now')));
		$attempts->field('attempts', $last['attempts']);
		$attempts->save();

		if (isset( $last['attempts']) && $last['attempts'] >= MAX_ATTEMPTS )
		{
			return false;
		}
		return true;
	}

	private function blockIPAddress() 
	{ 
		$attempts = new Mysql('dash_login_attempts');
		$last = array();
		$attempts->field('ip_address', $_SERVER['REMOTE_ADDR']);
		$last = $attempts->read();
		
		
		if (isset( $last['last_attempt'] ) && strtotime( $last['last_attempt'] ) > strtotime( LOCKOUT_INTERVAL ) )
		{
			if (isset( $last['attempts']) && $last['attempts'] >= MAX_ATTEMPTS )
			{
				return true;
			}
		}
		
		return false;
	}

	private function clearIPAddress() 
	{ 
		$attempts = new Mysql('dash_login_attempts');
		$last = array();
		$attempts->field('ip_address', $_SERVER['REMOTE_ADDR']);
		$last = $attempts->read();
		
		if (isset( $last['last_attempt'] ) && strtotime( $last['last_attempt'] ) > strtotime( LOCKOUT_INTERVAL ) )
		{
			// $attempts->delete();
			$db->delete('dash_login_attempts', 'ip_address', $_SERVER['REMOTE_ADDR']);
		}
	} 


	public function destroySession() {
		$this->setAuthCookie("", -3600);
		unset($_COOKIE[$this->sessionName]);

		$this->username = '';
		$this->userID = 0;

		return true;
	}

	private function getUser($username){
		
		$user = new Mysql('users');
		$user->field('username', $username);
		$dbCheck = $user->read();

		if(!empty($dbCheck)){
			return $dbCheck;
		}else{
			return false;
		}
	}
	
	public function setSession() {
		if ( isset( $_COOKIE[$this->sessionName] ) ) {
			if ($this->setAuthCookie(stripslashes($_COOKIE[$this->sessionName])))
				return true;
			else {
				$this->status = "Could not save session";
				return false;
			}
		}

		if ( !$this->isAuthenticated() ) return false;

		$loginData = array(
			'username' => $this->username,
			'realname' => $this->realname,
			'id' => $this->userID,
			'duration' => $this->sessionDuration
		);

		$cookie = json_encode( ($loginData) );

		if ($this->setAuthCookie($cookie))
			return true;
		else {
			$this->status = "Could not save session";
			return false;
		}
	}

	private function hashPassword( $password, $salt ) {
		if ( function_exists('hashPassword') ) {
			$newpass = hashPassword( $password, $salt );
			return $newpass;
		}
		$newpass = sha1( $password . $salt );
		return $newpass;
	}
	
	private function getExpiration(){
		return time() + $this->sessionDuration;
	}
	
	private function setAuthCookie($value, $duration = ""){
		if($duration == ""){
			$duration = $this->sessionDuration;
		}
		
		$url = parse_url($_SERVER["HTTP_HOST"]);
		$domain = isset($url['host']) ? $url['host'] : null;
		$dir = "/";
		$cookiedie = ($duration > 0) ? time()+(int)$duration : (int)$duration; //expire in one hour
		$cookiesecure = false;
		
		$var = $this->sessionName;
		
		// die(var_dump($domain));
		//die();
		return setcookie ($var, $value, $cookiedie, $dir, $domain, $cookiesecure);	
	}
}