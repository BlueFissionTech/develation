<?php

namespace BlueFission\Connections;

use BlueFission\Val;
use BlueFission\Arr;
use BlueFission\Str;
use BlueFission\IObj;
use BlueFission\Net\HTTP;
use BlueFission\Behavioral\IConfigurable;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Meta;

/**
 * Class Curl
 * 
 * This class implements the connection functionality using cURL. It extends the
 * base Connection class and implements the IConfigurable interface.
 */
class Curl extends Connection implements IConfigurable
{
	/**
	 * Result of the last cURL operation.
	 *
	 * @var string
	 */
	protected $_result;

	/**
	 * Options to use
	 *
	 * @var array
	 */
	protected $_options = [];

	/**
	 * Configuration data for the cURL connection.
	 *
	 * @var array
	 */
	protected $_config = [
		'target'=>'',
		'username'=>'',
		'password'=>'',
		'method'=>'',
		'headers'=>[],
		'refresh'=>false,
		'validate_host'=>false,
		'verify_ssl' => true,
		'verbose' => false,
	];
	
	/**
	 * Constructor that sets the configuration data.
	 *
	 * @param array|null $config Configuration data.
	 */
	public function __construct( $config = null )
	{
		parent::__construct();
		if (Arr::is($config))
			$this->config($config);
	}

	/**
	 * Sets options for the cURL connection.
	 *
	 * @param string $option Option to set.
	 * @param mixed $value Val of the option.
	 * 
	 * @return IObj
	 */
	public function option($option, $value): IObj
	{
		$this->_options[$option] = $value;

		return $this;
	}
	
	/**
	 * Opens a cURL connection.
	 *
	 * @return void
	 */
	protected function _open(): void
	{
		$status = '';
		$target = $this->config('target') ?? HTTP::domain();
		$refresh = (bool)$this->config('refresh');

		if ( $this->_connection ) {
			$this->close();
		}
		
		if ( !$this->config('validate_host') || HTTP::urlExists($target) )
		{
			$data = $this->_data;

			//open connection
			$this->_connection = curl_init();
			
			curl_setopt($this->_connection, CURLOPT_URL, $target);
			curl_setopt($this->_connection, CURLOPT_COOKIESESSION, $refresh);
			if (!empty($this->config('headers'))) {
				curl_setopt($this->_connection, CURLOPT_HTTPHEADER, $this->config('headers'));
			}

			if ( $this->config('verbose') ) {
				curl_setopt($this->_connection, CURLOPT_VERBOSE, true);
			}

			if ( $this->config('username') && $this->config('password') )
    			curl_setopt($this->_connection, CURLOPT_USERPWD, $this->config('username') . ':' . $this->config('password'));
			
			$status = $this->_connection ? self::STATUS_CONNECTED : self::STATUS_NOTCONNECTED;

			$this->perform( $this->_connection 
				? [Event::SUCCESS, Event::CONNECTED] : [Event::ACTION_FAILED, Event::FAILURE], new Meta(when: Action::CONNECT, info: $status ) );

		} else {
			$status = self::STATUS_FAILED;
			$this->perform( [Event::ACTION_FAILED, Event::FAILURE], new Meta(when: Action::CONNECT, info: $status ) );
		}

		$this->status($status);
	}
	
	/**
	 * Closes a cURL connection.
	 *
	 * @return void
	 */
	protected function _close (): void
	{
    	if ( $this->_connection) {
			curl_close($this->_connection);
		}
		$this->perform(State::DISCONNECTED);
	}
	
	/**
	 * Performs a cURL query to the target URL.
	 * 
	 * @param array $query The query data to be sent to the target URL.
	 * 
	 * @return IObj
	 */
	public function query($query = null): IObj
	{
		$this->perform(State::PERFORMING_ACTION, new Meta(when: Action::PROCESS));

		$curl = $this->_connection;
		$method = Str::lower($this->config('method'));
		
		if ($curl)
		{
			if (Val::isNotNull($query))
			{

				if (Arr::isAssoc($query))
				{
					$this->assign($query);
				}
			}
			$data = $this->_data->val();

			if (Arr::size($data) > 0) {
				$this->perform([Action::SEND, State::SENDING], new Meta(when: Action::PROCESS, data: $data));
			}
			//set the url, number of POST vars, POST data
			if ( $method == 'post' ) {
				if ( Arr::size($data) > 0 ) {
					curl_setopt($curl,CURLOPT_POST, count($data));
					curl_setopt($curl,CURLOPT_POSTFIELDS, HTTP::jsonEncode($data));
					$headers =  array_merge($this->config('headers'), ['Content-Type: application/json']);
					curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
				}

				if ( !$this->config('verify_ssl') || $this->config('verify_ssl') === 'false' ) {
					curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				}

			} elseif ( $method == 'get') {
				curl_setopt($curl, CURLOPT_URL, $this->config('target').'/'.HTTP::query($data));
			}
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			foreach ($this->_options as $option=>$value) {
				curl_setopt($curl, $option, $value);
			}
			
			//execute post
			$this->perform([State::RECEIVING, State::PROCESSING, State::BUSY]);
			$this->_result = curl_exec($curl);
			if ($this->_result === false) {
			    $error = curl_error($curl);
			    error_log('CURL Error: ' . $error);
			    $this->perform(Event::ERROR, new Meta(when: Action::PROCESS, info: $error));
			}

			$this->halt([State::BUSY, State::SENDING, State::RECEIVING, State::PROCESSING]);

			$this->perform(Event::SENT, new Meta(data: $data));
			$this->perform([Action::RECEIVE]);
			$this->perform(Event::RECEIVED, new Meta(data: $this->_result));

			$status = ( $this->_result ) ? self::STATUS_SUCCESS : ($error ?? self::STATUS_FAILED);

			$this->perform( 
				$this->_result ? [Event::SUCCESS, Event::COMPLETE, Event::PROCESSED] : [Event::ACTION_FAILED, Event::FAILURE], 
				new Meta(when: Action::PROCESS, info: $status ) 
			);
		}
		else
		{
			$status = self::STATUS_NOTCONNECTED;
			$this->perform( [Event::ACTION_FAILED, Event::FAILURE], new Meta(when: Action::PROCESS, info: $status ) );
		}
		$this->status($status);

		$this->halt(State::PERFORMING_ACTION);

		return $this;
	}

}