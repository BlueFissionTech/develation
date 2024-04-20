<?php
namespace BlueFission\Behavioral;

use BlueFission\Val;
use BlueFission\Arr;
use BlueFission\Str;
use BlueFission\IObj;
use BlueFission\Behavioral\Behaviors\Behavior;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\Meta;

/**
 * Trait Configurable
 * 
 * @package BlueFission\Behavioral
 * 
 * The Configurable trait is an implementation of the IConfigurable interface,
 * extending the functionality of the Scheme trait. It is used to define the
 * configuration of an object, as well as its current status.
 *
 * To be paired with IConfigurable interface.
 */
trait Configurable {
	use Behaves {
        Behaves::__construct as private __behavesConstruct;
    }

	/**
	 * @var array $_config The configuration for the object.
	 */
	// protected $_config;

	/**
	 * @var array $_status The current status of the object.
	 */
	protected $_status;
	
	/**
	 * Constructor for the Configurable trait. Initializes the parent trait, and sets
	 * the _config and _status properties to arrays if they are not already set. 
	 * Dispatches the State::NORMAL event.
	 */
	public function __construct( $config = null )
	{
		$this->__behavesConstruct( );
		if (!Val::is($this->_config)) {
			$this->_config = [];
		}
		
		if (!Val::is($this->_status)) {
			$this->_status = [];
		}

		$this->config($config);

		$this->dispatch( State::NORMAL );
	}
	
	/**
	 * Gets or sets the configuration of the object.
	 * 
	 * @param string|array|null $config The key for the configuration item to be retrieved,
	 * or an array of key-value pairs to set the configuration.
	 * @param mixed|null $value The value to be set for the configuration item.
	 * 
	 * @return mixed Returns the configuration array if $config is not provided,
	 * returns the value of the specified configuration item if $config is a string, 
	 * and returns null if the specified configuration item does not exist.
	 */
	public function config( $config = null, $value = null ): mixed
	{
		if (Val::isNull($config)) {
			return $this->_config;
		} elseif (Str::is($config)) {
			if (Val::isNull ($value)) {
				return $this->_config[$config] ?? null;
			}
						
			$this->perform( State::CONFIGURING );
			if ( !$this->is(State::READONLY) ) {
				if ( $this->is(State::DRAFT) ) {
					$this->_config[$config] = $value;
				} elseif (Arr::hasKey($this->_config, $config)) {
					$this->_config[$config] = $value; 
				}
			}
		} elseif (Arr::is($config) && !$this->is(State::READONLY)) {
			$this->perform( State::CONFIGURING );
			$this->perform( State::BUSY );
			if ( $this->is(State::DRAFT) ) {
				foreach ( $config as $a=>$b ) {
					$this->_config[$a] = $b;
				}
			} else {
				foreach ( $this->_config as $a=>$b ) {
					if ( Val::is($config[$a] )) $this->_config[$a] = $config[$a];
				}
			}
			$this->halt( State::BUSY );
		}

		$this->perform( Event::CONFIGURED );
		return $this;
	}
	
	/**
	 * Add a status message or retrieve the current status message.
	 *
	 * @param  string|null  $message  The status message to add. If not provided, the current status message is returned.
	 * @return mixed  The status message, or `null` if no message was provided.
	 */
	public function status($message = null): mixed
	{
		if (Val::isNull($message))
		{
			$message = end($this->_status);
			return $message;
		}
		$this->_status[] = $message;

		$this->perform( Event::MESSAGE, new Meta(info: $message) );

		return null;
	}

	/**
	 * Get or set the value of a field.
	 *
	 * @param  string  $field  The name of the field.
	 * @param  mixed|null  $value  The value to set for the field. If not provided, the current value of the field is returned.
	 * @return mixed|false  The field value, or `false` if the field does not exist and is not in a draft state.
	 */
	public function field( string $field, $value = null ): mixed
	{
		if (!$this instanceof IObj) {
            throw new \LogicException(
            	sprintf(
                    '%s must implement %s to use %s',
                    get_class($this),
                    IObj::class,
                    __TRAIT__
                )
            );
        }


        if ( $value && !$this->is( State::READONLY ) )
		{	
			if ( $this->is( State::DRAFT ) ) {
				parent::field($field, $value);
			} elseif ( Arr::hasKey($this->_data, $field) ) {
				parent::field($field, $value);
			}
			return $this;
		}
		
		return parent::field($field);
	}

	/**
	 * Assign values to fields in this object.
	 *
	 * @param  object|array  $data  The data to import into this object.
	 * @return IObj
	 * @throws InvalidArgumentException  If the data is not an object or associative array.
	 */
	public function assign( $data ): IObj
	{
		if (!$this instanceof IObj) {
            throw new \LogicException(
            	sprintf(
                    '%s must implement %s to use %s',
                    get_class($this),
                    IObj::class,
                    __TRAIT__
                )
            );
        }

		if ( is_object( $data ) || Arr::isAssoc( $data ) ) {
			$this->perform( State::CHANGING );
			foreach ( $data as $a=>$b ) {
				$this->field($a, $b);
			}
			$this->halt( State::CHANGING );
		} else {
			throw new \InvalidArgumentException( "Can't import from variable type " . gettype($data) );
		}

		return $this;
	}
}