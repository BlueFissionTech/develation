<?php
namespace BlueFission\Connections;

use BlueFission\Arr;
use BlueFission\IObj;
use BlueFission\Net\HTTP;
use BlueFission\Behavioral\IConfigurable;

/**
 * Class Socket
 *
 * This class is an implementation of the Connection class
 * that implements the IConfigurable interface.
 *
 * The class makes use of fsockopen() function to open a
 * socket connection.
 *
 * @package BlueFission\Connections
 */
class Socket extends Connection implements IConfigurable
{
    /**
     * @var string $result The result of the query
     */
    protected $_result;
    /**
     * @var array $_config The configuration data
     */
    protected $_config = [
        'target' => '',
        'port' => '80',
        'method' => 'GET',
    ];
    /**
     * @var string $host The host name
     */
    private $_host;
    /**
     * @var string $url The URL for the query
     */
    private $_url;

    /**
     * Constructor for the Socket class
     *
     * If a config is provided, it will be passed to the config() method.
     *
     * @param string|array $config
     */
    public function __construct($config = '')
    {
        parent::__construct();
        if (Arr::is($config)) {
            $this->config($config);
        }
    }

    /**
     * Method to open the socket connection
     *
     * The method makes use of the HTTP::urlExists() method
     * to check if the target URL exists. If it does, it will
     * parse the URL to get the host and path.
     *
     * The fsockopen() method is then used to open the socket connection.
     *
     * @return IObj
     */
    public function open(): IObj
    {
        if (HTTP::urlExists($this->config('target'))) {
            $target = parse_url($this->config('target'));

            $status = '';

            $this->_host = $target['host'] ? $target['host'] : HTTP::domain();
            $this->_url = $target['path'];
            $port = $target['port'] ? $target['port'] : $this->config('port');

            $this->_connection = fsockopen($this->_host, $port, $error_number, $error_string, 30);

            $status = ($this->_connection) ? self::STATUS_CONNECTED : $error_string . ': ' . $error_number;
        } else {
            $status = self::STATUS_NOTCONNECTED;
        }

        $this->status($status);

        return $this;
    }

    /**
     * Method to close the socket connection
     *
     * The method makes use of the fclose() method to close
     * the connection, and then calls the parent::close() method
     * to clean up.
     *
     * @return IObj
     */
    public function close(): IObj
    {
        fclose($this->_connection);

        // clean up
        parent::close();

        return $this;
    }
	
	/**
	 * Performs an HTTP query
	 *
	 * @param string|null $query The query to be performed. If not provided, the query will use the method specified in the config.
	 *
	 * @return IObj
	 */
	public function query( $query = null ): IObj
	{
		$socket = $this->_connection;
		$status = '';
		
		if ($socket) 
		{
			$method = $method ? $method : $this->config('method');
			
			$data = HTTP::query($this->_data);
			$method = strtoupper($method);
			$request = '';
			
			$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'PHP/'.phpversion();
			
			if ($method == 'GET') {
				$request .= '/' . $this->_url . '?';
				$request .= $data;
				$request .= "\r\n";
				$request .= "User-Agent: Dev-Elation\r\n"; 
				$request .= "Connection: Close\r\n";
				$request .= "Content-Length: 0\r\n";
				
				$cmd = "GET $request HTTP/1.0\r\nHost: ".$this->_host."\r\n\r\n";
			} elseif ($method == 'POSTS') {
				
				$request .= '/' . $this->_url;
				$request .= "\r\n";
				$request .= "User-Agent: Dev-Elation\r\n"; 
				$request .= "Content-Type: application/x-www-form-urlencoded\r\n" .
				$request .= "Content-Length: ".strlen($data)."\r\n";
				$request .= $data;
			} else {
				$status = self::STATUS_FAILED;
				$this->status($status);
				return false;
			}
			
			$cmd = "$method $request HTTP/1.1\r\nHost: ".$this->_host."\r\n";
			
			fputs($sock, $cmd);
			
			while (!feof($sock)) 
			{
				$data .= fgets($sock, 1024);
			}
			
			$this->_result = $data;
			$status = $this->_result ? self::STATUS_SUCCESS : self::STATUS_FAILED;
		}
		else
		{
			$status = self::STATUS_NOTCONNECTED;
		}	
		$this->status($status);

		return $this;
	}
}