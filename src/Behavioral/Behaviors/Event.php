<?php
namespace BlueFission\Behavioral\Behaviors;

/**
 * Class Event
 * 
 * Represents a behavioral Event in the BlueFission Behavioral system.
 */
class Event extends Behavior
{
	const LOAD = 'OnLoad';
	const UNLOAD = 'OnUnload';
	const ACTIVATED = 'OnActivated';
	const CHANGE = 'OnChange';
	const COMPLETE = 'OnComplete';
	const SUCCESS = 'OnSuccess';
	const FAILURE = 'OnFailure';
	const MESSAGE = 'OnMessageUpdate';
    const CONNECTED = 'OnConnected';
    const DISCONNECTED = 'OnDisconnected';
    const CLEAR_DATA = 'OnClearData';

    // CRUD operations
    const CREATED = 'OnCreated';
    const READ = 'OnRead';
    const UPDATED = 'OnUpdated';
    const SAVED = 'OnSaved';
    const DELETED = 'OnDeleted';

    // Data transmission
    const SENT = 'OnSent';
    const RECEIVED = 'OnReceived';

    // State changes
    const STATE_CHANGED = 'OnStateChanged';

    // More granular system events
    const AUTHENTICATED = 'OnAuthenticated';
    const AUTHENTICATION_FAILED = 'OnAuthenticationFailed';
    const SESSION_STARTED = 'OnSessionStarted';
    const SESSION_ENDED = 'OnSessionEnded';

    // Error and Exception Handling
    const ERROR = 'OnError';
    const EXCEPTION = 'OnException';

    // More specific application events
    const CONFIGURED = 'OnConfigured';
    const INITIALIZED = 'OnInitialized';
    const FINALIZED = 'OnFinalized';

    // Custom application logic
    const PROCESSED = 'OnProcessed';
    const ACTION_PERFORMED = 'OnActionPerformed';
    const ACTION_FAILED = 'OnActionFailed';

	/**
	 * Constructor for the Event class
	 *
	 * @param string $name The name of the event.
	 */
	public function __construct( $name )
	{
		parent::__construct( $name, 0, true, false );
	}
}
