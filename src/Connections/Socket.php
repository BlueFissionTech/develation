<?php
namespace BlueFission\Connections;

use BlueFission\Net\HTTP;
use BlueFission\Behavioral\IConfigurable;

class Socket extends Connection implements IConfigurable
{
	protected $_result;
	protected $_config = array( 'target'=>'',
		'port'=>'80',
		'method'=>'GET',
	);
	private $_host;
	private $_url;
	
	public function __construct( $config = '' )
	{
		parent::__construct();
		if (is_array($config))
			$this->config($config);
	}
	
	public function open ()
	{
		if ( HTTP::urlExists($this->config('target')) )
		{
			$target = parse_url( $this->config('target') );
			
			$status = '';
			
			$this->_host = $target['host'] ? $target['host'] : HTTP::domain();
			$this->_url = $target['path'];
			$port = $target['port'] ? $target['port'] : $this->config('port');
					
			$this->_connection = fsockopen($host, $port, $error_number, $error_string, 30);
			
			$status ($this->_connection) ? self::STATUS_CONNECTED : $error_string . ': '. $error_number; 
		}
		else
		{
			$status = self::STATUS_NOTCONNECTED;
		}
		
		$this->status($status);
	}
	
	public function close ()
	{
		fclose($this->_connection); 
			
		// clean up
		parent::close();
	}
	
	public function query( $query = null ) 
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
	}
}