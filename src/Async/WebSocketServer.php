<?php

namespace BlueFission\Async;

/**
 * Shared behavior for the optional Ratchet websocket handler.
 *
 * @internal
 */
trait WebSocketServerBehavior
{
    protected \SplObjectStorage $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage();
        echo "WebSocket server started.\n";
    }

    /**
     * Handles a new connection.
     *
     * @param object $conn
     * @return void
     */
    public function onOpen($conn): void
    {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    /**
     * Handles an incoming message from a client.
     *
     * @param object $from
     * @param string $msg
     * @return void
     */
    public function onMessage($from, $msg): void
    {
        echo sprintf("Message from %d: %s\n", $from->resourceId, $msg);

        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send($msg);
            }
        }
    }

    /**
     * Handles a closed connection.
     *
     * @param object $conn
     * @return void
     */
    public function onClose($conn): void
    {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    /**
     * Handles an error on a connection.
     *
     * @param object $conn
     * @param \Exception $e
     * @return void
     */
    public function onError($conn, \Exception $e): void
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

/**
 * Basic WebSocketServer implementation for the optional Ratchet transport.
 * Handles open, message, close, and error events for all clients.
 */
if (interface_exists(\Ratchet\MessageComponentInterface::class)) {
    class WebSocketServer implements \Ratchet\MessageComponentInterface
    {
        use WebSocketServerBehavior;
    }
} else {
    class WebSocketServer
    {
        use WebSocketServerBehavior;
    }
}
