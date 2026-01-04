<?php

namespace BlueFission\Behavioral\Behaviors;

/**
 * Class Event
 *
 * Represents a behavioral Event in the BlueFission Behavioral system.
 */
class Event extends Behavior
{
    public const LOAD = 'OnLoad';
    public const UNLOAD = 'OnUnload';
    public const ACTIVATED = 'OnActivated';
    public const CHANGE = 'OnChange';
    public const COMPLETE = 'OnComplete';
    public const STARTED = 'OnStarted';
    public const SUCCESS = 'OnSuccess';
    public const FAILURE = 'OnFailure';
    public const MESSAGE = 'OnMessageUpdate';
    public const CONNECTED = 'OnConnected';
    public const BLOCKED = 'OnBlocked';
    public const DISCONNECTED = 'OnDisconnected';
    public const CLEAR_DATA = 'OnClearData';

    // CRUD operations
    public const CREATED = 'OnCreated';
    public const READ = 'OnRead';
    public const UPDATED = 'OnUpdated';
    public const SAVED = 'OnSaved';
    public const DELETED = 'OnDeleted';

    // Data transmission
    public const SENT = 'OnSent';
    public const RECEIVED = 'OnReceived';

    // State changes
    public const STATE_CHANGED = 'OnStateChanged';

    // More granular system events
    public const AUTHENTICATED = 'OnAuthenticated';
    public const AUTHENTICATION_FAILED = 'OnAuthenticationFailed';
    public const SESSION_STARTED = 'OnSessionStarted';
    public const SESSION_ENDED = 'OnSessionEnded';

    // Error and Exception Handling
    public const ERROR = 'OnError';
    public const EXCEPTION = 'OnException';

    // More specific application events
    public const CONFIGURED = 'OnConfigured';
    public const INITIALIZED = 'OnInitialized';
    public const FINALIZED = 'OnFinalized';

    // Custom application logic
    public const PROCESSED = 'OnProcessed';
    public const STOPPED = 'OnStopped';
    public const ACTION_PERFORMED = 'OnActionPerformed';
    public const ACTION_FAILED = 'OnActionFailed';

    /**
     * Constructor for the Event class
     *
     * @param string $name The name of the event.
     */
    public function __construct($name)
    {
        parent::__construct($name, 0, true, false);
    }
}
