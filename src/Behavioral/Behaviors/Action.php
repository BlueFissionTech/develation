<?php
namespace BlueFission\Behavioral\Behaviors;

/**
 * Class Action
 * 
 * Represents a behavior that performs an action in response to an event
 */
class Action extends Behavior
{
	/**
	 * Constant value representing activation of the behavior
	 * 
	 * @var string
	 */
	const ACTIVATE = 'DoActivate';

	/**
	 * Constant value representing an update of the behavior
	 * 
	 * @var string
	 */
	const UPDATE = 'DoUpdate';

	// CRUD Operations
    const CREATE = 'DoCreate';
    const READ = 'DoRead';
    const DELETE = 'DoDelete';
    const SAVE = 'DoSave'; // General save action that could be create or update

    // User Interactions
    const CLICK = 'DoClick';
    const HOVER = 'DoHover';
    const SCROLL = 'DoScroll';
    const INPUT = 'DoInput';

    // System and Application
    const RUN = 'DoRun';
    const START = 'DoStart';
    const STOP = 'DoStop';
    const RESTART = 'DoRestart';
    const PAUSE = 'DoPause';
    const RESUME = 'DoResume';

    // Network and Communication
    const CONNECT = 'DoConnect';
    const DISCONNECT = 'DoDisconnect';
    const SEND = 'DoSend';
    const RECEIVE = 'DoReceive';
    const SYNC = 'DoSync';

    // Authentication
    const LOGIN = 'DoLogin';
    const LOGOUT = 'DoLogout';
    const AUTHENTICATE = 'DoAuthenticate';
    const AUTHORIZE = 'DoAuthorize';

    // Error handling
    const THROW_ERROR = 'DoThrowError';
    const CATCH_ERROR = 'DoCatchError';
    const HANDLE_EXCEPTION = 'DoHandleException';

    // Data manipulation and validation
    const VALIDATE = 'DoValidate';
    const FILTER = 'DoFilter';
    const TRANSFORM = 'DoTransform';

    // Application specific actions
    const PROCESS = 'DoProcess';
    const REFRESH = 'DoRefresh';
    const LOAD_MORE = 'DoLoadMore';

	/**
	 * Constructor
	 * 
	 * @param string $_name  The name of the action
	 */
	public function __construct( $_name )
	{
		parent::__construct( $_name, 0, false, true );
	}
}