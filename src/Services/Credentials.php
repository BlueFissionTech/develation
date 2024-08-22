<?php
namespace BlueFission\Services;

use BlueFission\Behavioral\Configurable;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Obj;

class Credentials extends Obj {
	use Configurable {
		Configurable::__construct as private __configConstruct;
	}

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
	protected $data = [
		'username'=>'',
		'password'=>'',
	];

	public function __construct() {
		$this->__configConstruct();
	}

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
}
