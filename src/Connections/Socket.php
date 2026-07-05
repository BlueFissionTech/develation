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
        'port' => '8080',
        'method' => 'GET',
        'timeout' => 5,
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
     * @return void
     */
    protected function _open(): void
    {
        if (HTTP::urlExists($this->config('target'))) {
            $target = parse_url($this->config('target'));

            $status = '';
            $scheme = $target['scheme'] ?? 'http';

            $this->_host = $target['host'] ?? HTTP::domain();
            $this->_url = ltrim((string)($target['path'] ?? ''), '/');
            $port = $target['port'] ?? (($scheme === 'https') ? 443 : $this->config('port'));
            $timeout = (float)$this->config('timeout');
            $endpoint = $scheme === 'https' ? "ssl://{$this->_host}" : $this->_host;

            $this->_connection = fsockopen($endpoint, $port, $error_number, $error_string, $timeout);

            if ($this->_connection) {
                stream_set_timeout($this->_connection, (int)$timeout);
            }

            $status = ($this->_connection)
            ? self::STATUS_CONNECTED : (($error_string) ? ($error_string . ': ' . $error_number) : self::STATUS_NOTCONNECTED);

            $this->perform($this->_connection
                ? [Event::SUCCESS, Event::CONNECTED] : [Event::ACTION_FAILED, Event::FAILURE], new Meta(when: Action::CONNECT, info: $status));

        } else {
            $status = self::STATUS_FAILED;
            $this->perform([Event::ACTION_FAILED, Event::FAILURE], new Meta(when: Action::CONNECT, info: $status));
        }

        $this->status($status);
    }

    /**
     * Method to close the socket connection
     *
     * The method makes use of the fclose() method to close
     * the connection, and then calls the parent::close() method
     * to clean up.
     *
     * @return void
     */
    protected function _close(): void
    {
        if ($this->_connection) {
            fclose($this->_connection);
        }
        $this->perform(State::DISCONNECTED);
    }

    /**
     * Performs an HTTP query
     *
     * @param string|null $query The query to be performed. If not provided, the query will use the method specified in the config.
     *
     * @return IObj
     */
    public function query($query = null): IObj
    {

        $this->perform(State::PERFORMING_ACTION, new Meta(when: Action::PROCESS));


        $socket = $this->_connection;
        $status = '';

        if ($socket) {
            $method = Str::make($this->config('method'))->upper()->val();

            $data = HTTP::query($this->_data);
            $this->_result = '';

            if (Str::isNotEmpty($data)) {
                $this->perform([Action::SEND, State::SENDING], new Meta(when: Action::PROCESS, data: $data));
            }

            $request = Str::make();

            if ($method == 'GET') {
                $request
                    ->append('/')
                    ->append($this->_url)
                    ->append('?')
                    ->append($data)
                    ->append("\r\n")
                    ->append("User-Agent: Dev-Elation\r\n")
                    ->append("Connection: Close\r\n")
                    ->append("Content-Length: 0\r\n");
            } elseif ($method == 'POST') {
                $request
                    ->append('/')
                    ->append($this->_url)
                    ->append("\r\n")
                    ->append("User-Agent: Dev-Elation\r\n")
                    ->append("Content-Type: application/x-www-form-urlencoded\r\n")
                    ->append("Content-Length: ")
                    ->append(Str::size($data))
                    ->append("\r\n")
                    ->append($data);
            } else {
                $status = self::STATUS_FAILED;
                $this->status($status);
                return false;
            }

            $cmd = Str::make($method)
                ->append(' ')
                ->append($request())
                ->append(' HTTP/1.1')
                ->append("\r\nHost: ")
                ->append($this->_host)
                ->append("\r\n")
                ->val();

            $this->perform([State::RECEIVING, State::PROCESSING, State::BUSY]);
            fputs($socket, $cmd);
            $response = Str::make();

            while (!feof($socket)) {
                $chunk = fgets($socket, 1024);
                $meta = stream_get_meta_data($socket);

                if (($meta['timed_out'] ?? false) === true) {
                    $this->perform(Event::ERROR, new Meta(when: Action::RECEIVE, info: "Socket read timed out"));
                    break;
                }

                if ($chunk === false) {
                    break;
                }

                $this->dispatch(Event::RECEIVED, new Meta(when: Action::RECEIVE, data: $chunk));

                $response->append($chunk);
            }
            $this->_result = $response->val();
            $this->halt([State::BUSY, State::RECEIVING, State::PROCESSING]);

            $status = $this->_result ? self::STATUS_SUCCESS : self::STATUS_FAILED;

            $this->perform(
                $this->_result ? [Event::SUCCESS, Event::COMPLETE, Event::PROCESSED] : [Event::ACTION_FAILED, Event::FAILURE],
                new Meta(when: Action::PROCESS, info: $status)
            );
        } else {
            $status = self::STATUS_NOTCONNECTED;
            $this->perform([Event::ACTION_FAILED, Event::FAILURE], new Meta(when: Action::PROCESS, info: $status));
        }

        $this->halt(State::PERFORMING_ACTION);
        $this->status($status);

        return $this;
    }
}
