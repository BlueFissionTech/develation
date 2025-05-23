<?php

namespace BlueFission\Behavioral\Behaviors;

/**
 * Class Action
 *
 * Represents a behavior that performs an action in response to an event.
 */
class Action extends Behavior
{
    // Core actions
    public const ACTIVATE = 'DoActivate';
    public const UPDATE = 'DoUpdate';

    // CRUD Operations
    public const CREATE = 'DoCreate';
    public const READ = 'DoRead';
    public const DELETE = 'DoDelete';
    public const SAVE = 'DoSave'; // Generic save action that can cover both create and update

    // User Interactions
    public const CLICK = 'DoClick';
    public const HOVER = 'DoHover';
    public const SCROLL = 'DoScroll';
    public const INPUT = 'DoInput';

    // System and Application
    public const RUN = 'DoRun';
    public const START = 'DoStart';
    public const STOP = 'DoStop';
    public const RESTART = 'DoRestart';
    public const PAUSE = 'DoPause';
    public const RESUME = 'DoResume';

    // Network and Communication
    public const CONNECT = 'DoConnect';
    public const DISCONNECT = 'DoDisconnect';
    public const SEND = 'DoSend';
    public const RECEIVE = 'DoReceive';
    public const SYNC = 'DoSync';

    // Authentication
    public const LOGIN = 'DoLogin';
    public const LOGOUT = 'DoLogout';
    public const AUTHENTICATE = 'DoAuthenticate';
    public const AUTHORIZE = 'DoAuthorize';

    // Error Handling
    public const THROW_ERROR = 'DoThrowError';
    public const CATCH_ERROR = 'DoCatchError';
    public const HANDLE_EXCEPTION = 'DoHandleException';

    // Data and Validation
    public const VALIDATE = 'DoValidate';
    public const FILTER = 'DoFilter';
    public const TRANSFORM = 'DoTransform';

    // App-Specific
    public const PROCESS = 'DoProcess';
    public const REFRESH = 'DoRefresh';
    public const LOAD_MORE = 'DoLoadMore';

    /**
     * Constructor for an Action behavior.
     *
     * @param string $name The name of the action
     */
    public function __construct(string $name)
    {
        parent::__construct($name, 0, false, true);
    }
}

/**
 *  Improvement Summary:
 * - Added full docblocks to the class and constructor for clarity
 * - Grouped constants by logical categories (CRUD, UI, Network, etc.)
 * - Cleaned up inline comments to be concise and consistent
 * - Typed constructor parameter as `string` for strict mode safety
 * - Ensured format matches Behavior standards and is ready for IDE hints
 */
