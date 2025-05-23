<?php

namespace BlueFission\Connections;

use BlueFission\Val;
use BlueFission\Arr;
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

            $this->_host = $target['host'] ?? HTTP::domain();
            $this->_url = $target['path'] ?? '';
            $port = $target['port'] ?? $this->config('port');

            $this->_connection = fsockopen($this->_host, $port, $error_number, $error_string, 30);

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
            $method = $this->config('method');

            $data = HTTP::query($this->_data);

            if (Val::is($data)) {
                $this->perform([Action::SEND, State::SENDING], new Meta(when: Action::PROCESS, data: $data));
            }

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
            } elseif ($method == 'POST') {

                $request .= '/' . $this->_url;
                $request .= "\r\n";
                $request .= "User-Agent: Dev-Elation\r\n";
                $request .= "Content-Type: application/x-www-form-urlencoded\r\n";
                $request .= "Content-Length: ".strlen($data)."\r\n";
                $request .= $data;
            } else {
                $status = self::STATUS_FAILED;
                $this->status($status);
                return false;
            }

            $cmd = "$method $request HTTP/1.1\r\nHost: ".$this->_host."\r\n";

            $this->perform([State::RECEIVING, State::PROCESSING, State::BUSY]);
            fputs($socket, $cmd);

            while (!feof($socket)) {
                $chunk = fgets($socket, 1024);
                $this->dispatch(Event::RECEIVED, new Meta(when: Action::RECEIVE, data: $chunk));

                $this->_result .= $chunk;
            }
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
