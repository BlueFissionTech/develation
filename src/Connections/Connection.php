<?php
namespace BlueFission\Connections;

use BlueFission\DevObject;
use BlueFission\Behavioral\IConfigurable;
use BlueFission\Behavioral\Configurable;

/**
 * Class Connection
 * 
 * An abstract class that defines the structure for database connections.
 */
abstract class Connection extends DevObject implements IConfigurable
{	
    use Configurable {
        Configurable::__construct as private __configConstruct;
    };
    /**
     * Connection resource
     *
     * @var resource|null
     */
	protected $_connection = null;

    /**
     * Query result
     *
     * @var mixed|null
     */
	protected $_result = null;
	
    /**
     * Constant for connected status
     */
	const STATUS_CONNECTED = 'Connected.';

    /**
     * Constant for not connected status
     */
	const STATUS_NOTCONNECTED = 'Not Connected.';

    /**
     * Constant for disconnected status
     */
	const STATUS_DISCONNECTED = 'Disconnected.';

    /**
     * Constant for success status
     */
	const STATUS_SUCCESS = 'Query success.';

    /**
     * Constant for failed status
     */
	const STATUS_FAILED = 'Query failed.';
	
    /**
     * Connection constructor
     *
     * @param array|null $config
     */
	public function __construct( $config = null )
	{
        $this->__configConstruct();
		parent::__construct();
		$this->status( self::STATUS_NOTCONNECTED );
		if (is_array($config)) {
			$this->config($config);
        }
	}
		
    /**
     * Abstract method to open a connection
     */
	abstract public function open();
		
    /**
     * Close the connection
     */
	public function close()
	{
		$this->_connection = null;
		$this->status(self::STATUS_DISCONNECTED);
	}
	
    /**
     * Abstract method to run a query on the connection
     *
     * @param string|null $query
     */
	abstract public function query( $query = null);
	
    /**
     * Get the result of a query
     *
     * @return mixed|null
     */
	public function result( )
	{
		return $this->_result;
	}
}
