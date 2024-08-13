<?php
/**
 * Class Authenticator
 *
 * This class extends the Configurable class and implements the authenticate method which
 * verifies a given username and password and returns true or false based on the result. 
 * The isAuthenticated method returns true if the session is authenticated.
 */
namespace BlueFission\Services;

use BlueFission\Val;
use BlueFission\Behavioral\Behaviors\Behavior;
use BlueFission\Behavioral\Configurable;
use BlueFission\Data\IData;
use BlueFission\Net\HTTP;
use BlueFission\Data\Storage\Storage;

class Authenticator extends Service {
	use Configurable {
        Configurable::__construct as private __configConstruct;
	}
	/**
	 * Default configuration values
	 *
	 * @var array
	 */
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

	/**
	 * The user data
	 *
	 * @var array
	 */
	protected $_data = [
		'id'=>'',
		'username'=>'',
		'displayname'=>'',
		'remember'=>'',
	];

	/**
	 * The data source object
	 *
	 * @var Storage
	 */
	private $_datasource;

	/**
	 * The session object
	 *
	 * @var Storage
	 */
	private $_session;

	/**
	 * The password verification function
	 *
	 * @var callable
	 */
	private $_verificationFunction;

	/**
	 * The Authenticator constructor
	 *
	 * @param Storage $datasource
	 * @param array|null $config
	 */
	public function __construct( Storage $session, Storage $datasource, $config = null ) {
		$this->__configConstruct($config);
		parent::__construct();
		// if (is_array($config)) {
		// 	$this->config($config);
        // }
        $this->_datasource = $datasource;

        $session->config('name', $this->config('session'));
        $session->activate();
		$this->_session = $session;
	}

	/**
	 * Authenticates the user
	 *
	 * @param string $username
	 * @param string $password
	 *
	 * @return boolean
	 */
	public function authenticate( $username, $password ) {
		// $users = $this->config('users');
		// $users->

		if (isset($_SERVER['REMOTE_ADDR']) && !$this->confirmIPAddress($_SERVER['REMOTE_ADDR']) ) {
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

		if ( empty($savedpass) || !$this->verifyPassword($password, $savedpass) ) {
			$this->_status[] = "Username or password incorrect";
			return false;
		}

		$this->username = $userinfo[$this->config('username_field')];
		$this->id = $userinfo[$this->config('id_field')];
		
		return true;
	}

	private function verifyPassword($password, $savedpass) {
		if (! $this->_verificationFunction ) {
			$this->_verificationFunction = function($password, $savedpass) {
				return password_verify($password, $savedpass);
			};
		}

		return $this->_verificationFunction($password, $savedpass);
	}

	/**
	 * Method isAuthenticated
	 * 
	 * Check if user is authenticated
	 * 
	 * @return bool Returns true if user is authenticated, false otherwise
	 */
	public function isAuthenticated() {
		$this->_session->read();
		$data = $this->_session->data();
		if ( Val::isNotEmpty( $data ) ) {
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

	/**
	 * Method confirmIPAddress
	 * 
	 * Confirm the IP address of the user
	 * 
	 * @param string $value The IP address of the user
	 * 
	 * @return bool Returns true if the IP address is confirmed, false otherwise
	 */
	private function confirmIPAddress($value) 
	{ 
		// $attempts = new Mysql('dash_login_attempts'); // TODO fix this with dependency injection
		$attempts = $this->_datasource;
		$attempts->config('name', $this->config('login_attempts_table'));
		$attempts->activate();
		$last = [];
		$attempts->field('ip_address', $value);
		$attempts->read();
		$last = $attempts->data();

		
		if (isset( $last['last_attempt'] ) && strtotime( $last['last_attempt'] ) > strtotime( $this->config('lockout_interval') ) )
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

	/**
	 * Method blockIPAddress
	 * 
	 * Block an IP address
	 * 
	 * @return bool Returns true if the IP address is blocked, false otherwise
	 */
	private function blockIPAddress() 
	{ 
		// $attempts = new Mysql('dash_login_attempts');
		$attempts = $this->_datasource;
		$attempts->config('name', $this->config('login_attempts_table'));
		$attempts->activate();
		$last = [];
		$attempts->field('ip_address', $_SERVER['REMOTE_ADDR']);
		$attempts->read();
		$last = $attempts->data();

		
		if (isset( $last['last_attempt'] ) && strtotime( $last['last_attempt'] ) > strtotime( $this->config('lockout_interval') ) )
		{
			if (isset( $last['attempts']) && $last['attempts'] >= $this->config('max_attempts') )
			{
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Clear the IP address from the login attempts table.
	 */
	private function clearIPAddress() 
	{ 
		$attempts = $this->_datasource;
		$attempts->clear();
		$attempts->config('name', $this->config('login_attempts_table'));
		$attempts->activate();
		$last = [];
		$attempts->field('ip_address', $_SERVER['REMOTE_ADDR']);
		$attempts->read();
		$last = $attempts->data();

		if (isset( $last['last_attempt'] ) && strtotime( $last['last_attempt'] ) > strtotime( $this->config('lockout_interval') ) )
		{
			$db->delete('dash_login_attempts', 'ip_address', $_SERVER['REMOTE_ADDR']);
		}
	}

	/**
	 * Destroy the current session.
	 */
	public function destroySession() {
		$this->setAuthCookie([], -3600);
		$this->_session->clear();
		$this->_session->write();
		$this->_session->delete();

		$this->displayname = '';
		$this->username = '';
		$this->id = 0;

		return true;
	}

	/**
	 * Get a user based on the provided username.
	 * @param string $username The username of the user to get.
	 * @return mixed The user data if the user was found, otherwise false.
	 */
	private function getUser($username){
		$user = $this->_datasource;
		$user->reset();
		$user->clear();
		$user->config('name', $this->config('credentials_table'));
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

	/**
	 * Set the session.
	 * @return bool True if the session was successfully set, otherwise false.
	 */
	public function setSession() {
		// $this->_session->read();
		// $data = $this->_session->data();
		// die(var_dump($_SESSION));

		if ( isset( $_COOKIE[$this->config('session')] ) ) {
			if ($this->setAuthCookie(stripslashes($_COOKIE[$this->config('session')]))) {
				return true;
			} else {
				$this->_status[] = "Could not save session";
				return false;
			}
		}

		if ( !$this->isAuthenticated() ) return false;
		$loginData = [
			'username' => $this->username,
			'id' => $this->id,
			'duration' => $this->config('duration')
		];

		// $cookie = HTTP::jsonEncode( ($loginData) );

		if ($this->setAuthCookie($loginData, $loginData['duration'])) {
			return true;
		} else {
			$this->_status[] = "Could not save session";
			return false;
		}
	}

	/**
	 * Get the expiration time for the cookie
	 *
	 * @return int The time the cookie should expire
	 */
	private function getExpiration(){
		return time() + $this->config('duration');
	}

	/**
	 * Set the authentication cookie with the given value
	 *
	 * @param string $value The value to set in the cookie
	 * @param string $duration The duration the cookie should be set for
	 *
	 * @return HTTP::cookie The newly set cookie
	 */
	private function setAuthCookie($value, $duration = "") {
		/*
		if($duration == ""){
			$duration = $this->config('duration');
		}
		
		$url = parse_url($_SERVER["HTTP_HOST"]);
		$domain = isset($url['host']) ? $url['host'] : null;
		$dir = "/";
		$cookiedie = ($duration > 0) ? time()+(int)$duration : (int)$duration; //expire in one hour
		$cookiesecure = false;
		
		$var = $this->config('session');
		*/
	
		$this->_session->clear();
		$this->_session->username = $value['username'];
		$this->_session->id = $value['id'];

		$this->_session->write();

		return true;
	}

}