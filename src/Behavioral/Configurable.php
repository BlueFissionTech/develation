<?php
namespace BlueFission\Behavioral;

use BlueFission\DevValue;
use BlueFission\DevArray;
use BlueFission\DevString;
use BlueFission\Behavioral\Behaviors\Behavior;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Action;

/**
 * Class Configurable
 * 
 * @package BlueFission\Behavioral
 * 
 * The Configurable class is an implementation of the IConfigurable interface,
 * extending the functionality of the Scheme class. It is used to define the
 * configuration of an object, as well as its current status.
 */
class Configurable extends Scheme implements IConfigurable {
	/**
	 * @var array $_config The configuration for the object.
	 */
	protected $_config;
	/**
	 * @var array $_status The current status of the object.
	 */
	protected $_status;
	
	/**
	 * Constructor for the Configurable class. Initializes the parent class, and sets
	 * the _config and _status properties to arrays if they are not already set. 
	 * Dispatches the State::NORMAL event.
	 */
	public function __construct( )
	{
		parent::__construct( );
		if (!isset($this->_config))
			$this->_config = [];
		
		if (!isset($this->_status))
			$this->_status = [];

		$this->dispatch( State::NORMAL );
	}
	
	/**
	 * Gets or sets the configuration of the object.
	 * 
	 * @param string|array|null $config The key for the configuration item to be retrieved,
	 * or an array of key-value pairs to set the configuration.
	 * @param mixed|null $value The value to be set for the configuration item.
	 * 
	 * @return array|mixed|null Returns the configuration array if $config is not provided,
	 * returns the value of the specified configuration item if $config is a string, 
	 * and returns null if the specified configuration item does not exist.
	 */
	public function config( $config = null, $value = null )
	{
		if (DevValue::isEmpty($config)) {
			return $this->_config;
		} elseif (DevString::is($config)) {
			if (DevValue::isEmpty ($value)) {
				return isset($this->_config[$config]) ? $this->_config[$config] : null;
			}
						
			if ( ( array_key_exists($config, $this->_config) || $this->is(State::DRAFT) ) && !$this->is(State::READONLY)) {
				$this->_config[$config] = $value; 
			}
		} elseif (DevArray::is($config) && !$this->is(State::READONLY)) {
			$this->perform( State::BUSY );
			if ( $this->is(State::DRAFT) ) {
				foreach ( $config as $a=>$b ) {
					$this->_config[$a] = $config[$a];
				}
			} else {
				foreach ( $this->_config as $a=>$b ) {
					if ( isset($config[$a] )) $this->_config[$a] = $config[$a];
				}
			}
			$this->halt( State::BUSY );
		}
	}
	
	/**
	 * Add a status message or retrieve the current status message.
	 *
	 * @param  string|null  $message  The status message to add. If not provided, the current status message is returned.
	 * @return string|null  The status message, or `null` if no message was provided.
	 */
	public function status($message = null)
	{
		if (DevValue::isNull($message))
		{
			$message = end($this->_status);
			return $message;
		}
		$this->_status[] = $message;

		$this->perform( Event::MESSAGE );
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
		if ( DevArray::hasKey($this->_data, $field) || $this->is( State::DRAFT ) )
		{	
			$value = parent::field($field, $value);
			return $value;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Assign values to fields in this object.
	 *
	 * @param  object|array  $data  The data to import into this object.
	 * @throws InvalidArgumentException  If the data is not an object or associative array.
	 */
	public function assign( $data )
	{
		if ( is_object( $data ) || DevArray::isAssoc( $data ) ) {
			$this->perform( State::BUSY );
			foreach ( $data as $a=>$b ) {
				$this->field($a, $b);
			}
			$this->halt( State::BUSY );
			$this->dispatch( Event::CHANGE );
		}
		else
			throw new \InvalidArgumentException( "Can't import from variable type " . gettype($data) );
	}

	/**
	 * Initialize the object.
	 *
	 * @return void
	 */
	protected function init()
	{
		parent::init();
		$this->behavior( new Event( Event::MESSAGE ) );
	}
}