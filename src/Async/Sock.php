<?php

namespace BlueFission\Async;

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use BlueFission\Behavioral\Configurable;
use BlueFission\Behavioral\IConfigurable;
use BlueFission\Behavioral\IDispatcher;
use BlueFission\Behavioral\Behaviors\Event;

/**
 * Sock class sets up a WebSocket server using Ratchet.
 * It supports configuration for host, port, and handler class.
 */
class Sock implements IDispatcher, IConfigurable
{
    use Configurable {
        Configurable::__construct as private __configConstruct;
    }

    private $_server;
    private $_port;

    protected array $_config = [
        'host' => 'localhost',
        'port' => 8080,
        'path' => null, // Optional path for advanced routing
        'class' => WebSocketServer::class, // Your WebSocket handler class
    ];

    /**
     * Constructor for Sock server.
     *
     * @param int $port Port to run the WebSocket server on
     * @param array $config Optional additional config
     */
    public function __construct(int $port = 8080, array $config = [])
    {
        $this->__configConstruct($config);

        $this->_port = $port;
        $this->config($config);
    }

    /**
     * Starts the WebSocket server.
     *
     * @return void
     */
    public function start(): void
    {
        $this->status("Starting WebSocket server on port {$this->config('port')}");

        $class = $this->config('class');

        $webSocket = new WsServer(new $class());
        $server = IoServer::factory(
            new HttpServer($webSocket),
            $this->config('port'),
            $this->config('host')
        );

        $this->_server = $server;

        $this->perform(Event::INITIALIZED);
        $server->run();
    }

    /**
     * Stops the WebSocket server.
     *
     * @return void
     */
    public function stop(): void
    {
        if ($this->_server) {
            $this->_server->socket->close();
            $this->_server = null;
            $this->perform(Event::FINALIZED);
            $this->status("WebSocket server stopped.");
        }
    }
}


/**
 * Improvement Summary:
 * - Added missing return types like `: void` for clarity and static analysis
 * - Removed syntax error (dangling `'` at end of original file)
 * - Fully documented the class and methods using PHPDoc
 * - Typed `$_config` property as `array` for clarity
 * - Used lifecycle event hooks like `Event::INITIALIZED` and `Event::FINALIZED`
 * - Improved log/status messaging for better developer feedback
 * - Ensured config handler class uses `new $class()` pattern safely
 */
