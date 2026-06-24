<?php

namespace BlueFission\Async;

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

    private const TRANSPORT_CLASSES = [
        \Ratchet\Server\IoServer::class,
        \Ratchet\Http\HttpServer::class,
        \Ratchet\WebSocket\WsServer::class,
        \Ratchet\MessageComponentInterface::class,
    ];

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
     * Check whether the optional Ratchet websocket transport is installed.
     */
    public static function isAvailable(): bool
    {
        return self::missingDependencies() === [];
    }

    /**
     * List missing classes required by the optional Ratchet transport.
     *
     * @param array<string>|null $classes
     * @return array<string>
     */
    public static function missingDependencies(?array $classes = null): array
    {
        $missing = [];

        foreach ($classes ?? self::TRANSPORT_CLASSES as $class) {
            if (!class_exists($class) && !interface_exists($class)) {
                $missing[] = $class;
            }
        }

        return $missing;
    }

    /**
     * Starts the WebSocket server.
     *
     * @return void
     */
    public function start(): void
    {
        $missing = self::missingDependencies();

        if ($missing !== []) {
            throw new \RuntimeException(
                'The Ratchet websocket transport is not installed. Install cboden/ratchet to use '
                . self::class
                . '. Missing: '
                . implode(', ', $missing)
            );
        }

        $this->status("Starting WebSocket server on port {$this->config('port')}");

        $class = $this->config('class');

        if (!is_string($class) || !class_exists($class)) {
            throw new \InvalidArgumentException('WebSocket handler class is not available.');
        }

        $handler = new $class();

        if (!$handler instanceof \Ratchet\ComponentInterface) {
            throw new \InvalidArgumentException('WebSocket handler must implement Ratchet\\ComponentInterface.');
        }

        $webSocket = new \Ratchet\WebSocket\WsServer($handler);
        $server = \Ratchet\Server\IoServer::factory(
            new \Ratchet\Http\HttpServer($webSocket),
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
