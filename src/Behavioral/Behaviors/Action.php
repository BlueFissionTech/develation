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
	const ACTIVATE = 'DoActivate';
	const UPDATE = 'DoUpdate';

	// CRUD Operations
	const CREATE = 'DoCreate';
	const READ = 'DoRead';
	const DELETE = 'DoDelete';
	const SAVE = 'DoSave'; // Generic save action that can cover both create and update

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

	// Error Handling
	const THROW_ERROR = 'DoThrowError';
	const CATCH_ERROR = 'DoCatchError';
	const HANDLE_EXCEPTION = 'DoHandleException';

	// Data and Validation
	const VALIDATE = 'DoValidate';
	const FILTER = 'DoFilter';
	const TRANSFORM = 'DoTransform';

	// App-Specific
	const PROCESS = 'DoProcess';
	const REFRESH = 'DoRefresh';
	const LOAD_MORE = 'DoLoadMore';

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
