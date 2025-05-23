<?php

namespace BlueFission\Async;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

/**
 * Basic WebSocketServer implementation using Ratchet.
 * Handles open, message, close, and error events for all clients.
 */
class WebSocketServer implements MessageComponentInterface
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
     * @param ConnectionInterface $conn
     * @return void
     */
    public function onOpen(ConnectionInterface $conn): void
    {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    /**
     * Handles an incoming message from a client.
     *
     * @param ConnectionInterface $from
     * @param string $msg
     * @return void
     */
    public function onMessage(ConnectionInterface $from, $msg): void
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
     * @param ConnectionInterface $conn
     * @return void
     */
    public function onClose(ConnectionInterface $conn): void
    {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    /**
     * Handles an error on a connection.
     *
     * @param ConnectionInterface $conn
     * @param \Exception $e
     * @return void
     */
    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

/**
 * Improvement Summary:
 * - Added PHPDoc blocks to all methods for IDE support and clarity
 * - Used `: void` return types to explicitly mark no-return functions
 * - Typed `$clients` as `\SplObjectStorage` for better type safety
 * - Improved echo/log format consistency
 * - Ensured compliance with `MessageComponentInterface` fully and cleanly
 */
