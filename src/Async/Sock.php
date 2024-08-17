<?php

namespace BlueFission\Async;

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use BlueFission\Behavioral\Configurable;
use BlueFission\Behavioral\IConfigurable;
use BlueFission\Behavioral\IDispatcher;
use BlueFission\Behavioral\Behaviors\Event;

class Sock implements IDispatcher, IConfigurable {
    use Configurable {
        Configurable::__construct as private __configConstruct;
    }

    private $_server;
    private $_port;

    protected $_config = [
        'host' => 'localhost',
        'port' => '8080',
        'path' => null, // Optional: path where the WebSocket server should serve
        'class' => WebSocketServer::class, // Your WebSocket handler class
    ];

    public function __construct($port = 8080, $config = []) {
        
        $this->__configConstruct($config);

        $this->_port = $port;
        $this->config($config);
    }

    public function start() {
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

    public function stop() {
        if ($this->_server) {
            $this->_server->socket->close();
            $this->_server = null;
            $this->perform(Event::FINALIZED);
            $this->status("WebSocket server stopped.");
        }
    }
}
