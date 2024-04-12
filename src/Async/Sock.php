<?php

namespace BlueFission\Async;

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use BlueFission\Behavioral\Configurable;
use BlueFission\Behavioral\Behaviors\Event;

class Sock {
    use Configurable {
        Configurable::__construct as private __configConstruct;
    };

    private $server;
    private $port;

    public function __construct($port = 8080, $config = []) {
        
        __configConstruct($config);

        $this->port = $port;
        $this->config([
            'host' => 'localhost',
            'port' => $this->port,
            'path' => null, // Optional: path where the WebSocket server should serve
            'class' => 'MyApp\WebSocketServer', // Your WebSocket handler class
        ]);
    }

    public function start() {
        $this->status("Starting WebSocket server on port {$this->config('port')}");
        $webSocket = new WsServer(new $this->config('class')());
        $server = IoServer::factory(
            new HttpServer($webSocket),
            $this->config('port'),
            $this->config('host')
        );

        $this->server = $server;
        $this->perform(Event::INITIALIZED);
        $server->run();
    }

    public function stop() {
        if ($this->server) {
            $this->server->socket->close();
            $this->server = null;
            $this->perform(Event::FINALIZED);
            $this->status("WebSocket server stopped.");
        }
    }
}
