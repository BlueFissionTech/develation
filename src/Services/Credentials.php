<?php
namespace BlueFission\Services;
// @include_once('Loader.php');
// $loader = Loader::instance();
// $loader->load('com.bluefission.develation.DevConfigurable');

use BlueFission\Behavioral\Configurable;
use BlueFission\Behavioral\Behaviors\Event;

class Credentials extends Configurable
{
    /**
     * Error message for an empty username
     */
	const FAILED_USERNAME_EMPTY = 'Username cannot be empty';

    /**
     * Error message for an empty password
     */
	const FAILED_PASSWORD_EMPTY = 'Password cannot be empty';

    /**
     * The data containing the username and password
     *
     * @var array
     */
	protected $_data = array(
		'username'=>'',
		'password'=>'',
	);

    /**
     * Validates the username and password
     *
     * @return bool
     */
	public function validate()
	{
		$valid = false;
		
		if ( !$this->field('username') )
			$this->status( self::FAILED_USERNAME_EMPTY );
		elseif ( !$this->field('password') )
			$this->status( self::FAILED_PASSWORD_EMPTY );
		else
			$valid = true;

		if ( $valid == true )
			$this->dispatch( Event::SUCCESS );

		return $valid;
	}

    /**
     * Initializes the class
     */
	protected function init()
	{
		parent::init();
	}
}
