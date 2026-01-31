<?php

namespace BlueFission\Async;

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use BlueFission\Behavioral\Behaves;
use BlueFission\Behavioral\Configurable;
use BlueFission\Behavioral\IConfigurable;
use BlueFission\Behavioral\IDispatcher;
use BlueFission\Behavioral\IBehavioral;
use BlueFission\Behavioral\Behaviors\Event;

/**
 * Sock class sets up a WebSocket server using Ratchet.
 * It supports configuration for host, port, and handler class.
 */
class Sock implements IDispatcher, IConfigurable, IBehavioral
{
    use Behaves {
        Behaves::__construct as private __behavesConstruct;
    }
    use Configurable;

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
        $this->__behavesConstruct();
        $this->bootstrapConfig($config);

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
