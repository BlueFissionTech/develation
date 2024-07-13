<?php

namespace BlueFission\Connections;

use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\Behavioral\IConfigurable;
use BlueFission\Val;
use BlueFission\Arr;
use BlueFission\IObj;

/**
 * Class Stdio
 * 
 * This class is designed to handle standard input/output operations extending
 * the Connection class functionality to stdio.
 */
class Stdio extends Connection implements IConfigurable
{

    /**
     * Configuration data for the STDIO connection.
     *
     * @var array
     */
    protected $_config = [
        'target' => null,
        'output' => null,
    ];

    /**
     * Constructor that sets the configuration data.
     *
     * @param array|null $config Configuration data.
     */
    public function __construct( $config = null )
    {
        parent::__construct();
        if (Arr::is($config)) {
            $this->config($config);
        }
    }

    /**
     * Opens the standard input or output as a stream.
     * 
     * @param string $mode 'input' for stdin, 'output' for stdout
     * @return void
     */
    protected function _open(): void
    {
        $this->close();

        $this->_connection;
        $this->_connection = [
            'in' => $this->config('target') ? fopen($this->config('target'), 'r') : (defined('STDIN') ? STDIN : fopen('php://input', 'r')),
            'out' => $this->config('output') ? fopen($this->config('output'), 'w') : (defined('STDOUT') ? STDOUT : fopen('php://output', 'w'))
        ];

        $status = $this->_connection['in'] && $this->_connection['out'] ? self::STATUS_CONNECTED : self::STATUS_NOTCONNECTED;

        $this->perform( 
            $this->_connection['in'] && $this->_connection['out'] 
            ? [Event::SUCCESS, Event::CONNECTED, State::CONNECTED] : [Event::ACTION_FAILED, Event::FAILURE], new Meta(when: Action::CONNECT, info: $status ) );


        if ( $this->_connection['in'] ) {
            stream_set_blocking($this->_connection['in'], false);
        }

        $this->status($status);
    }

    /**
     * Continuously reads data from standard input in a non-blocking way.
     * 
     * @return void
     */
    private function listen()
    {
        // $this->perform(State::BUSY);

        $readStreams = [$this->_connection['in']];
        $writeStreams = null;
        $exceptStreams = null;
        $timeout = 0; // No timeout, return immediately
        $captured = false;

        $numChangedStreams = @stream_select($readStreams, $writeStreams, $exceptStreams, $timeout);

        if ($numChangedStreams === false) {
            // Error occurred during stream_select
            $error = "stream_select error";
            error_log('IO Error: ' . $error);
            $this->perform(Event::ERROR, new Meta(when: Action::PROCESS, info: $error));
        } elseif ($numChangedStreams > 0) {
            // Data is available for reading
            $data = fgets($this->_connection['in']);

            if ($data !== false) {
                $this->_result .= $data;
                $this->dispatch(Event::RECEIVED, new Meta(data: $data)); // Emit success with data
                $captured = true;
            } else {
                $error = "No data received before EOF";
                error_log('IO Error: ' . $error);
                $this->perform(Event::ERROR, new Meta(when: Action::PROCESS, info: $error));
            }
        }

        // $this->halt(State::BUSY);
    }


    /**
     * Writes data to standard output.
     * 
     * @param string $data Data to write
     * @return $this
     */
    public function send($data)
    {
        $this->perform([Action::SEND, State::SENDING], new Meta(when: Action::PROCESS, data: $data));
        if (fwrite($this->_connection['out'], $data) !== false) {
            $this->perform(Event::SENT, new Meta(data: $data)); // Emit success with data
        } else {
            $this->perform(Event::ERROR, new Meta(info: 'Failed to write data')); // Emit failure
        }
        $this->halt(State::SENDING);

        return $this;
    }

    /**
     * Close the connection (STDIN or STDOUT)
     * 
     * @return void
     */
    protected function _close(): void
    {
        if (isset($this->_connection['in']) && is_resource($this->_connection['in'])) {
            fclose($this->_connection['in']);
        }
        if (isset($this->_connection['out']) && is_resource($this->_connection['out'])) {
            fclose($this->_connection['out']);
        }
        $this->perform(Event::DISCONNECTED); // Signal that the stream has been unloaded
    }

    /**
     * Helper method to run a query (mainly for sending data).
     * 
     * @param string|null $query Optional data for write
     */
    public function query($query = null)
    {
        $this->perform(State::PERFORMING_ACTION, new Meta(when: Action::PROCESS));
        if ( $this->is(State::BUSY) ) {
            return;
        }

        $this->perform(State::PROCESSING);
        $this->listen();

        $status = ( $this->_result ) ? self::STATUS_SUCCESS : self::STATUS_FAILED;

        $this->perform( 
            $this->_result ? [Event::SUCCESS, Event::COMPLETE, Event::PROCESSED] : [Event::ACTION_FAILED, Event::FAILURE], 
            new Meta(when: Action::PROCESS, info: $status ) 
        );

        $this->status($status);

        $this->halt([State::PERFORMING_ACTION, State::PROCESSING, ]);

        return $this;
    }
}