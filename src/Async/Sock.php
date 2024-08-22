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
 * Class Sock
 * 
 * Sock class provides an implementation of a WebSocket server using Ratchet library. 
 * It integrates with the event-driven architecture and configurable behavior of the system.
*/
class Sock implements IDispatcher, IConfigurable {
    use Configurable {
        Configurable::__construct as private __configConstruct;
    }

    /**
	 * @var IoServer|null The WebSocket server instance.
	*/
    private $server;

    /**
	 * @var int The port on which the WebSocket server will listen.
	*/
    private $port;

    /**
	 * @var array The default configuration settings for the WebSocket server.
	*/

    protected $config = [
        'host' => 'localhost', // Host address for the WebSocket server
        'port' => '8080',      // Port for the WebSocket server
        'path' => null,        // Optional: Path where the WebSocket server should serve
        'class' => WebSocketServer::class, // Your WebSocket handler class
    ];

    /**
	 * Sock constructor.
	 * 
	 * Initializes the WebSocket server with a specified port and configuration.
	 * 
	 * @param int $port The port number to start the WebSocket server on.
	 * @param array $config An optional configuration array to override default settings.
	*/
    public function __construct( $port = 8080, $config = [] ) {
        // Initialize configurable properties using the Configurable trait constructor
        $this->__configConstruct( $config );

        // Set the WebSocket server port
        $this->port = $port;

        // Apply any custom configurations passed into the constructor
        $this->config( $config );
    }

    /**
	 * Starts the WebSocket server.
	 * 
	 * Configures and runs the WebSocket server using the Ratchet library, triggering the
	 * Event::INITIALIZED event when the server is successfully started.
	*/
    public function start() {
        // Output the status of the WebSocket server initialization
        $this->status( "Starting WebSocket server on port {$this->config( 'port' )}" );

        // Fetch the WebSocket handler class from the configuration
        $class = $this->config( 'class' );
        
        // Instantiate the WebSocket server with the specified handler class
        $webSocket = new WsServer( new $class() );
        
        // Create the IoServer instance using HttpServer and WebSocket server
        $server = IoServer::factory( 
            new HttpServer( $webSocket ),
            $this->config( 'port' ),
            $this->config( 'host' )
         );

        // Store the server instance for later use
        $this->server = $server;

        // Trigger the initialized event to notify listeners that the server has started
        $this->perform( Event::INITIALIZED );

        // Run the WebSocket server ( blocking call )
        $server->run();
    }

    /**
	 * Stops the WebSocket server.
	 * 
	 * Closes the WebSocket server socket and triggers the Event::FINALIZED event to
	 * notify listeners that the server has been stopped.
	*/
    public function stop() {
        // Check if the server is running
        if ( $this->server ) {
            // Close the server socket
            $this->server->socket->close();

            // Set the server instance to null
            $this->server = null;

            // Trigger the finalized event to notify listeners that the server has stopped
            $this->perform( Event::FINALIZED );

            // Output the status of the WebSocket server being stopped
            $this->status( "WebSocket server stopped." );
        }
    }
}