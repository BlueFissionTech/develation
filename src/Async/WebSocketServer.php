<?php

namespace BlueFission\Async;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

/**
 * The WebSocketServer class implements the MessageComponentInterface to handle WebSocket connections.
 * It manages connected clients and facilitates sending and receiving messages across the WebSocket server.
*/
class WebSocketServer implements MessageComponentInterface {
    /**
	 * @var \SplObjectStorage The storage for connected WebSocket clients.
	*/
    protected $clients;

    /**
	 * Constructor method for initializing the WebSocket server.
	 * 
	 * Initializes the SplObjectStorage instance to store client connections.
	*/
    public function __construct() {
        // Initialize the SplObjectStorage to hold connected clients
        $this->clients = new \SplObjectStorage();

        // Log a message indicating that the WebSocket server has started
        echo "WebSocket server started.\n";
    }

    /**
	 * Handles new WebSocket connections.
	 * 
	 * Attaches the new connection to the clients list and logs the connection ID.
	 * 
	 * @param ConnectionInterface $conn The new WebSocket connection.
	*/
    public function onOpen( ConnectionInterface $conn ) {
        // Attach the new connection to the clients storage
        $this->clients->attach( $conn );

        // Log the connection resource ID
        echo "New connection! ( {$conn->resourceId} )\n";
    }

    /**
	 * Handles incoming messages from WebSocket clients.
	 * 
	 * Broadcasts the received message to all connected clients except the sender.
	 * 
	 * @param ConnectionInterface $from The client that sent the message.
	 * @param string $msg The message that was sent.
	*/
    public function onMessage( ConnectionInterface $from, $msg ) {
        // Log the message and the resource ID of the sender
        echo sprintf( 'Message from %d: %s' . "\n", $from->resourceId, $msg );

        // Iterate through all connected clients
        foreach ( $this->clients as $client ) {
            // If the client is not the sender, broadcast the message
            if ( $from !== $client ) {
                // If sender is not the receiver, send to each client connected
                $client->send( $msg );
            }
        }
    }

    /**
	 * Handles the closing of WebSocket connections.
	 * 
	 * Detaches the connection from the clients list and logs the disconnection event.
	 * 
	 * @param ConnectionInterface $conn The connection that is closing.
	*/
    public function onClose( ConnectionInterface $conn ) {
        // Detach the connection from the clients storage
        $this->clients->detach( $conn );

        // Log the resource ID of the disconnected client
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    /**
	 * Handles errors that occur on the WebSocket connection.
	 * 
	 * Logs the error message and closes the connection.
	 * 
	 * @param ConnectionInterface $conn The connection where the error occurred.
	 * @param \Exception $e The exception thrown during the error.
	*/
    public function onError( ConnectionInterface $conn, \Exception $e ) {
        // Log the error message
        echo "An error has occurred: {$e->getMessage()}\n";

        // Close the connection that caused the error
        $conn->close();
    }
}