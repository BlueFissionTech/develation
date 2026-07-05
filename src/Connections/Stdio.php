<?php

namespace BlueFission\Connections;

use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\Behavioral\IConfigurable;
use BlueFission\Val;
use BlueFission\Arr;
use BlueFission\Ref;
use BlueFission\Str;
use BlueFission\IObj;
use BlueFission\DevElation as Dev;

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
    public function __construct($config = null)
    {
        parent::__construct();
        if (Arr::is($config)) {
            $this->config($config);
        }
    }

    /**
     * Read request/body input without using stream_select().
     *
     * @param mixed $source Null for php://input, a stream resource, or a readable stream/path string.
     * @return string
     */
    public static function readInput(mixed $source = null): string
    {
        $source = Val::isNull($source) ? 'php://input' : Dev::apply('_in', $source);
        if (is_resource($source)) {
            $ref = Ref::resource($source);
        } elseif (Str::is($source) && Str::isNotEmpty($source)) {
            $handle = @fopen($source, 'r');
            $ref = is_resource($handle) ? Ref::resource($handle, ['owned' => true]) : null;
        } else {
            $ref = null;
        }

        if (!$ref || !$ref->valid()) {
            return (string)Dev::apply('_out', '');
        }

        $contents = $ref->read();
        $ref->close();

        if ($contents === false) {
            $contents = '';
        }

        return (string)Dev::apply('_out', $contents);
    }

    /**
     * Alias for readInput().
     *
     * @param mixed $source Null for php://input, a stream resource, or a readable stream/path string.
     * @return string
     */
    public static function input(mixed $source = null): string
    {
        return self::readInput($source);
    }

    /**
     * Read a single line from a stream without closing caller-owned handles.
     *
     * @param mixed $source Null for STDIN/php://stdin, a stream resource, or a readable stream/path string.
     * @return string
     */
    public static function readLine(mixed $source = null): string
    {
        if (Val::isNull($source)) {
            $source = defined('STDIN') ? STDIN : 'php://stdin';
        } else {
            $source = Dev::apply('_in', $source);
        }

        if (is_resource($source)) {
            $ref = Ref::resource($source);
        } elseif (Str::is($source) && Str::isNotEmpty($source)) {
            $handle = @fopen($source, 'r');
            $ref = is_resource($handle) ? Ref::resource($handle, ['owned' => true]) : null;
        } else {
            $ref = null;
        }

        if (!$ref || !$ref->valid()) {
            return (string)Dev::apply('_out', '');
        }

        $line = $ref->line();
        $ref->close();

        if ($line === false) {
            $line = '';
        }

        return (string)Dev::apply('_out', $line);
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

        $target = $this->config('target');
        $output = $this->config('output');
        $this->_connection = [
            'in' => Str::isNotEmpty($target) ? fopen($target, 'r') : (defined('STDIN') ? STDIN : fopen('php://input', 'r')),
            'out' => Str::isNotEmpty($output) ? fopen($output, 'w') : (defined('STDOUT') ? STDOUT : fopen('php://output', 'w'))
        ];

        $status = $this->_connection['in'] && $this->_connection['out'] ? self::STATUS_CONNECTED : self::STATUS_NOTCONNECTED;

        $this->perform(
            $this->_connection['in'] && $this->_connection['out']
            ? [Event::SUCCESS, Event::CONNECTED, State::CONNECTED] : [Event::ACTION_FAILED, Event::FAILURE],
            new Meta(when: Action::CONNECT, info: $status)
        );


        if ($this->_connection['in']) {
            stream_set_blocking($this->_connection['in'], false);
        }

        $this->status($status);
    }

    /**
     * Continuously reads data from standard input in a non-blocking way.
     *
     * @return void
     */
    protected function listen()
    {
        // $this->perform(State::BUSY);

        $readStreams = [$this->_connection['in']];
        $writeStreams = null;
        $exceptStreams = null;
        $timeout = 0; // No timeout, return immediately
        $captured = false;

        $numChangedStreams = @stream_select($readStreams, $writeStreams, $exceptStreams, $timeout);

        $result = Str::make();

        if ($numChangedStreams === false) {
            // Error occurred during stream_select
            $error = "stream_select error";
            error_log('IO Error: ' . $error);
            $this->perform(Event::ERROR, new Meta(when: Action::PROCESS, info: $error));
        } elseif ($numChangedStreams > 0) {
            // Data is available for reading
            $data = fgets($this->_connection['in']);

            if ($data !== false) {
                $result->append($data);
                $this->dispatch(Event::RECEIVED, new Meta(data: $data)); // Emit success with data
                $captured = true;
            } else {
                $error = "No data received before EOF";
                error_log('IO Error: ' . $error);
                $this->perform(Event::ERROR, new Meta(when: Action::PROCESS, info: $error));
            }
        }
        $this->_result = $result->val();

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
        if (Arr::is($this->_connection) && Arr::hasKey($this->_connection, 'in') && is_resource($this->_connection['in'])) {
            fclose($this->_connection['in']);
        }
        if (Arr::is($this->_connection) && Arr::hasKey($this->_connection, 'out') && is_resource($this->_connection['out'])) {
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
        if ($this->is(State::BUSY)) {
            return;
        }

        $this->perform(State::PROCESSING);
        $this->listen();

        $status = ($this->_result) ? self::STATUS_SUCCESS : self::STATUS_FAILED;

        $this->perform(
            $this->_result ? [Event::SUCCESS, Event::COMPLETE, Event::PROCESSED] : [Event::ACTION_FAILED, Event::FAILURE],
            new Meta(when: Action::PROCESS, info: $status)
        );

        $this->status($status);

        $this->halt([State::PERFORMING_ACTION, State::PROCESSING, ]);

        return $this;
    }
}
