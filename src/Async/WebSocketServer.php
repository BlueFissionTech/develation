<?php

namespace BlueFission\Async;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class WebSocketServer implements MessageComponentInterface {
    protected $_clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage();
        echo "WebSocket server started.\n";
    }

    public function onOpen(ConnectionInterface $_conn) {
        // Store the new connection
        $this->clients->attach($_conn);
        echo "New connection! ({$_conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $_msg) {
        echo sprintf('Message from %d: %s' . "\n", $from->resourceId, $_msg);

        // Send a message to all connected clients
        foreach ($this->clients as $_client) {
            if ($from !== $_client) {
                // The sender is not the receiver, send to each client connected
                $_client->send($_msg);
            }
        }
    }

    public function onClose(ConnectionInterface $_conn) {
        // The connection is closed, remove it
        $this->clients->detach($_conn);
        echo "Connection {$_conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $_conn, \Exception $_e) {
        echo "An error has occurred: {$_e->getMessage()}\n";
        $_conn->close();
    }
}
