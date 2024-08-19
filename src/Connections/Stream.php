<?php
namespace BlueFission\Connections;

use BlueFission\Val;
use BlueFission\Str;
use BlueFission\Arr;
use BlueFission\IObj;
use BlueFission\Net\HTTP;
use BlueFission\Behavioral\IConfigurable;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Meta;

/**
 * Class Stream
 * 
 * This class provides a stream connection for sending and receiving data over HTTP.
 * 
 * @package BlueFission\Connections
 * @implements IConfigurable
 */
class Stream extends Connection implements IConfigurable
{
	/**
	 * @var array $config Configuration options for the stream connection
	 */
	protected $config = [
		'target' => '',  // target URL for the stream connection
		'wrapper' => 'http', // wrapper for the stream context
		'method' => 'GET',  // HTTP method for the stream connection
		'header' => "Content-type: application/x-www-form-urlencoded\r\n", // header for the stream connection
	];

	/**
	 * The Resource handle
	 * @var null
	 */
	private $handle = null;
	
	/**
	 * Stream constructor.
	 *
	 * @param mixed|null $config Configuration options for the stream connection
	 */
	public function __construct( $config = null )
	{
		parent::__construct($config);		
	}
	
	/**
	 * Opens a stream connection.
	 *
	 * @return void
	 */
	protected function _open(): void
	{
		if ($this->connection) {
			$this->close();
		}

		$target = $this->config('target') ?? HTTP::domain();
		$method = $this->config('method');
		$header = $this->config('header'); 
		$wrapper = $this->config('wrapper');
		
		// Check if target URL exists
		if ( HTTP::urlExists($target) )
		{
			$this->config('target', $target);
			// Create a stream context with the options provided in the config
			$options = [
				$wrapper => [
					'header'	=>	$header,
					'method'	=>	$method,
				],
			];
			$this->connection = stream_context_create($options);
			// Set the connection status
			$status = $this->connection ? self::STATUS_CONNECTED : self::STATUS_NOTCONNECTED;

			$this->perform( $this->connection ? [Event::SUCCESS, Event::CONNECTED] : [Event::ACTION_FAILED, Event::FAILURE], new Meta(when: Action::CONNECT, info: $status ) );
		}
		else
		{
			$status = self::STATUS_FAILED;
			$this->perform( [Event::ACTION_FAILED, Event::FAILURE], new Meta(when: Action::CONNECT, info: $status ) );
		}

		$this->status($status);
	}

	protected function _close(): void
	{
		if ($this->handle) {
			fclose($this->handle);
		}
		$this->perform(State::DISCONNECTED);
	}

	
	/**
	 * Sends a query to the target URL and retrieves the result.
	 *
	 * @param mixed|null $query Query to be sent to the target URL
	 * @return IObj
	 */
	public function query ( $query = null ): IObj
	{ 
		$this->perform(State::PERFORMING_ACTION, new Meta(when: Action::PROCESS));

		// Set the connection status as not connected
		$status = self::STATUS_NOTCONNECTED;
		$context = $this->connection;
		$wrapper = $this->config('wrapper');
		$target = $this->config('target');

		$this->result = false;
		
		// If the stream context exists
		if ($context) {
			// If a query is not null
			if (Val::isNotNull($query)) {
				if (Arr::isAssoc($query)) {
					$this->assign($query); 
				} elseif (Str::is($query)) {
					$data = urlencode($query);
				}
			}
			
			$data = $data ?? HTTP::query( $this->data );

			if (!Val::isEmpty($data) || Arr::size($data) > 0) {
				$this->perform([Action::SEND, State::SENDING], new Meta(when: Action::PROCESS, data: $data));
			}
			
			stream_context_set_option ( $context, $wrapper, 'content', $data );
			if ( !$this->handle ) {
				$this->handle = fopen($target, 'r', false, $context);
				$this->halt(State::SENDING);
				$this->perform(Event::SENT, new Meta(data: $data));
			}

			if ($this->handle) {
				$this->perform([Action::RECEIVE, State::RECEIVING, State::PROCESSING, State::BUSY]);
			    while (!feof($this->handle)) {
			    	$chunk = fread($this->handle, 8192);
					$this->dispatch(Event::RECEIVED, new Meta(when: Action::RECEIVE, data: $chunk));

					$this->result .= $chunk;
			    }
				$this->halt([State::BUSY, State::RECEIVING, State::PROCESSING]);
			} else {
				$this->perform(Event::ERROR, new Meta(when: Action::RECEIVE, info: "Failed to open stream") );
			}

			$this->status( $this->result !== false ? self::STATUS_SUCCESS : self::STATUS_FAILED );

			$this->perform( 
				$this->result ? [Event::SUCCESS, Event::COMPLETE, Event::PROCESSED] : [Event::ACTION_FAILED, Event::FAILURE], 
				new Meta(when: Action::PROCESS, info: $status ) 
			);

		} else {
			$status = self::STATUS_NOTCONNECTED;
			$this->perform( [Event::ACTION_FAILED, Event::FAILURE], new Meta(when: Action::PROCESS, info: $status ) );
		}

		$this->halt(State::PERFORMING_ACTION);
		$this->status($status);

		return $this;
	}
}