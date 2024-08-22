<?php
namespace BlueFission\Behavioral;

use BlueFission\Val;
use BlueFission\Str;
use BlueFission\Arr;
use BlueFission\Obj;
use BlueFission\Collections\Collection;
use BlueFission\Exceptions\NotImplementedException;
use BlueFission\Behavioral\Behaviors\Behavior;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\Meta;
use InvalidArgumentException;

/**
 * Trait Behaves
 * 
 * A Behaves is an extension of the Dispatches trait that provides
 * additional behaviors and control structures for managing the state
 * of objects.
 *
 * To be paired with IBehavioral
 *
 * @package BlueFission\Behavioral
 */
trait Behaves 
{
	use Dispatches {
        Dispatches::__construct as private __dispatchesConstruct;
    }
    /**
     * Collection to store history of performed behaviors.
     *
     * @var Collection
     */
    protected $history;

    /**
     * Collection to store the current state of the object.
     *
     * @var Collection
     */
    protected $state;

    /**
     * Determines whether the object can have multiple states at once.
     *
     * @var bool
     */
    protected $multistate = true;

    /**
     * Behavioral constructor.
     */
    public function __construct()
    {
        $this->__dispatchesConstruct();

        $this->history = new Collection();
        $this->state = new Collection();

        $this->init();

        $this->perform( State::DRAFT );
    }

    /**
     * Performs behaviors on the object.
     *
     * @param string|Behavior $behavior The behavior to perform.
     */
    public function perform( ): IDispatcher
    {
        $args = func_get_args();
        $behaviors = array_shift( $args );

        if ( !Arr::is($behaviors) ) {
			$behaviors = [$behaviors];
		}

		foreach ($behaviors as $behavior) {
        	$this->_execute($behavior, $args );
        }

		return $this;
	}

	/**
     * Executes a behavior on the object.
     *
     * @param string|Behavior $behavior The behavior to perform.
     * @throws InvalidArgumentException If an invalid behavior type is passed.
     * @throws NotImplementedException If the behavior is not implemented.
     */
	private function _execute($behavior, $args = [] ) {
        if ($this->is(State::BUSY) ) {
        	$this->dispatch([Event::BLOCKED, Event::MESSAGE], new Meta(info: "Object is busy"));
			
        	return;
        }

		if ( Str::is($behavior) ) {
            $behaviorName = Str::grab();
        } elseif ( !($behavior instanceof Behavior) ) {
        	$this->trigger(Event::EXCEPTION);
            throw new InvalidArgumentException("Invalid Behavior Type");
        } else {
            $behaviorName = $behavior->name();
        }

        if ( $this->can( $behaviorName ) )
        {
            $behavior = ($behavior instanceof Behavior) ? $behavior : $this->behaviors->get($behaviorName);

            if (!$behavior) {
            	return $this;
            }
            
            if ($behavior->target == null) {
                $behavior->target = $this;
            }

            if ($behavior->context == null) {
                $behavior->context = $args;
            }

            $this->history->add($behaviorName, $behaviorName);

            // Handle States
            if ( $this->behaviors->has( $behaviorName ) && $this->behaviors->get( $behaviorName )->is_persistent() )
            {
            	$this->dispatch( State::STATE_CHANGING );
                if ( !$this->multistate ) {
                    $this->state->clear();
                }
                $this->state->add($behaviorName, $behaviorName);
                $this->dispatch( Event::STATE_CHANGED );
            }

            if (count($args) == 1 && $args[0] instanceof Meta ) {
            	$args = $args[0];
            }

            // Perform the behavior
            $this->dispatch( $behavior, $args );

            // Handle Actions
            if ( $this->behaviors->has( $behaviorName ) && !$this->behaviors->get( $behaviorName )->is_passive() )
            {
        		// Check if this action has a function in the handler
        		if ( $this->handlers->has( $behaviorName ) ) {
        			$this->trigger( Event::ACTION_PERFORMED );
        		}
            }

		}
		else
		{
			$this->trigger([Event::EXCEPTION]);
			throw new NotImplementedException("Behavior '{$behaviorName}' is not implemented in ". get_class($this) .".");
		}
	}

	/**
	 * Repeats another Dispatcher's selected behaviors
	 *
	 * @param IDispatcher $otherObject The other object to repeat the behaviors from
	 * @param mixed $behaviors The behaviors to repeat
	 */
	public function echo( IDispatcher $otherObject, $behaviors ): IDispatcher
	{
        if (!is_array($behaviors)) {
            $behaviors = [$behaviors];
        }

        foreach ($behaviors as $behavior) {
            $otherObject->when($behavior, function() use ($behavior) {
                $this->perform($behavior);
            });
        }

        return $this;
    }

	/**
	 * Check if the behavior can be performed.
	 * 
	 * @param string $behaviorName The name of the behavior.
	 * 
	 * @return bool True if the behavior can be performed, false otherwise.
	 */
	public function can( $behaviorName )
	{
		$can = ( ( $this->behaviors->has( $behaviorName ) || $this->is( State::DRAFT ) ) && !$this->is( State::BUSY ) );
		return $can;
	}

	/**
	 * Check if the object has a specific behavior.
	 * 
	 * @param string $behaviorName The name of the behavior to check for.
	 * 
	 * @return mixed The last behavior if $behaviorName is null,
	 * 				true if the object has the behavior,
	 * 				false otherwise.
	 */
	public function is( $behaviorName = null )
	{
		if ( $behaviorName ) {
			return $this->state->has( $behaviorName );
		} else {
			return $this->state->last();
		}
	}

	/**
	 * Halt the specified behavior.
	 * 
	 * @param string $behaviorName The name of the behavior to halt.
	 */
	public function halt( $behaviorName ): IDispatcher
	{
		if ( !is_array($behaviorName) ) {
			$behaviorName = [$behaviorName];
		}

		foreach ($behaviorName as $behavior) {
			$this->state->remove( $behavior );
		}

		return $this;
	}

	/**
	 * Initialize the object.
	 */
	protected function init()
	{
		// Basic system events
		$this->behavior( new Event( Event::LOAD ) );
		$this->behavior( new Event( Event::UNLOAD ) );
		$this->behavior( new Event( Event::ACTIVATED ) );
		$this->behavior( new Event( Event::CHANGE ) );
		$this->behavior( new Event( Event::STARTED ) );
		$this->behavior( new Event( Event::COMPLETE ) );
		$this->behavior( new Event( Event::SUCCESS ) );
		$this->behavior( new Event( Event::FAILURE ) );
		$this->behavior( new Event( Event::MESSAGE ) );
		$this->behavior( new Event( Event::BLOCKED ) );
	    $this->behavior( new Event( Event::CLEAR_DATA ) );
	    $this->behavior( new Event( Event::CONNECTED ), function($behavior) {
            $this->halt( State::CONNECTING );
            $this->perform( State::CONNECTED );
        });
	    $this->behavior( new Event( Event::DISCONNECTED ), function($behavior) {
            $this->halt( State::CONNECTED );
            $this->halt( State::DISCONNECTING );
            $this->perform( State::DISCONNECTED );
        });

	    // CRUD operations
	     $this->behavior(new Event( Event::READ ), function($behavior) {
            $this->halt( State::READING );
        });
        $this->behavior(new Event( Event::CREATED ), function($behavior) {
            $this->halt( State::SAVING );
            $this->halt( State::CREATING );
        });
        $this->behavior(new Event( Event::UPDATED ), function($behavior) {
            $this->halt( State::SAVING );
            $this->halt( State::UPDATING );
        });
        $this->behavior(new Event( Event::SAVED ), function($behavior) {
            $this->halt( State::SAVING );
        });
        $this->behavior(new Event( Event::DELETED ), function($behavior) {
            $this->halt( State::DELETING );
        });

	    // Data transmission
	    $this->behavior( new Event( Event::SENT ), function($behavior) {
	    	$this->halt( State::SENDING );
	    });
	    $this->behavior( new Event( Event::RECEIVED), function($behavior) {
	    	$this->halt( State::RECEIVING );
	    });

	    // State changes
	    $this->behavior( new Event( Event::STATE_CHANGED ) );

	    // More granular system events
	    $this->behavior( new Event( Event::AUTHENTICATED ), function($behavior) {
	    	$this->halt( State::AUTHENTICATING );
	    	$this->perform( State::AUTHENTICATED );
	    });
	    $this->behavior( new Event( Event::AUTHENTICATION_FAILED ), function($behavior) {
	    	$this->halt( State::AUTHENTICATING );
	    	$this->perform( State::UNAUTHENTICATED );
	    });
	    $this->behavior( new Event( Event::SESSION_STARTED ), function($behavior) {
	    	$this->halt( State::SESSION_STARTING );
	    });
	    $this->behavior( new Event( Event::SESSION_ENDED ), function($behavior) {
	    	$this->halt( State::SESSION_ENDING );
	    });

	    // Error and Exception Handling
	    $this->behavior( new Event( Event::ERROR ) );
	    $this->behavior( new Event( Event::EXCEPTION ) );

	    // More specific application events
	    $this->behavior( new Event( Event::CONFIGURED ), function($behavior) {
	    	$this->halt( State::CONFIGURING );
	    });
	    $this->behavior( new Event( Event::INITIALIZED ), function($behavior) {
	    	$this->halt( State::INITIALIZING );
	    });
	    $this->behavior( new Event( Event::FINALIZED ), function($behavior) {
	    	$this->halt( State::FINALIZING );
	    });

	    // Custom application logic
	    $this->behavior( new Event (Event::STOPPED ), function($behavior) {
	    	$this->halt( State::RUNNING );
	    });
	    $this->behavior( new Event( Event::PROCESSED ), function($behavior) {
	    	$this->halt( State::PROCESSING );
	    });
	    $this->behavior( new Event( Event::ACTION_PERFORMED ), function($behavior) {
	    	$this->halt( State::PERFORMING_ACTION );
	    });
	    $this->behavior( new Event( Event::ACTION_FAILED ), function($behavior) {
	    	$this->halt( State::PERFORMING_ACTION );
	    });

	    // User Interaction
		$this->behavior( new State( State::DRAFT ) );
		$this->behavior( new State( State::DONE ) );
		$this->behavior( new State( State::NORMAL ) );
		$this->behavior( new State( State::READONLY ) );
		$this->behavior( new State( State::BUSY ) );
		$this->behavior( new State( State::IDLE ) );
	    $this->behavior( new State( State::LOADING ) );
	    $this->behavior( new State( State::SAVING ) );
	    $this->behavior( new State( State::EDITING ) );
	    $this->behavior( new State( State::VIEWING ) );
	    $this->behavior( new State( State::PENDING ) );
	    $this->behavior( new State( State::APPROVED ) );
	    $this->behavior( new State( State::REJECTED ) );
	    $this->behavior( new State( State::ARCHIVED ) );
	    $this->behavior( new State( State::FULFILLED ) );
	    $this->behavior( new State( State::RUNNING ) );
	    $this->behavior( new State( State::CHANGING ) );

	    // State changes
	    $this->behavior( new State( State::STATE_CHANGING ) );

	    // CRUD Operations
	    $this->behavior( new State( State::CREATING ) );
	    $this->behavior( new State( State::READING ) );
	    $this->behavior( new State( State::UPDATING ) );
	    $this->behavior( new State( State::DELETING ) );

	    // Authentication and Authorization
	    $this->behavior( new State( State::AUTHENTICATING ) );
	    $this->behavior( new State( State::AUTHENTICATED ) );
	    $this->behavior( new State( State::UNAUTHENTICATED ) );
	    $this->behavior( new State( State::AUTHORIZATION_GRANTED ) );
	    $this->behavior( new State( State::AUTHORIZATION_DENIED ) );
	    $this->behavior( new State( State::SESSION_STARTING ) );
	    $this->behavior( new State( State::SESSION_ENDING ) );

	    // Network and Communication
	    $this->behavior( new State( State::CONNECTING ) );
	    $this->behavior( new State( State::CONNECTED ) );
	    $this->behavior( new State( State::DISCONNECTING ) );
	    $this->behavior( new State( State::DISCONNECTED ) );

	    // Data State
	    $this->behavior( new State( State::SYNCING ), function($behavior) {
	    	$this->halt( State::SYNCED );
	    });
	    $this->behavior( new State( State::SYNCED ), function($behavior) {
	    	$this->halt( State::SYNCING );
	    	$this->halt( State::OUT_OF_SYNC );
	    });
	    $this->behavior( new State( State::OUT_OF_SYNC ) );
	    $this->behavior( new State( State::SENDING ) );
	    $this->behavior( new State( State::RECEIVING ) );

	    // Operational
	    $this->behavior( new State( State::OPERATIONAL ), function($behavior) {
	    	$this->halt( State::NON_OPERATIONAL );
	    });
	    $this->behavior( new State( State::NON_OPERATIONAL ), function($behavior) {
	    	$this->halt( State::OPERATIONAL );
	    });
	    $this->behavior( new State( State::MAINTENANCE ) );
	    $this->behavior( new State( State::DEGRADED ) );
	    $this->behavior( new State( State::FAILURE ) );

	    // User Interaction
	    $this->behavior( new State( State::INTERACTING ) );
	    $this->behavior( new State( State::NON_INTERACTIVE ) );

	    // Custom application states
	    $this->behavior( new State( State::CONFIGURING ) );
	    $this->behavior( new State( State::INITIALIZING ) );
	    $this->behavior( new State( State::FINALIZING ) );
	    $this->behavior( new State( State::PROCESSING ) );
	    $this->behavior( new State( State::STOPPED ) );
	    $this->behavior( new State( State::WAITING_FOR_INPUT ) );
	    $this->behavior( new State( State::PERFORMING_ACTION ) );
	    $this->behavior( new State( State::ACTION_COMPLETED ) );
	    $this->behavior( new State( State::ERROR_STATE ) );
		
		// Actions
		$this->behavior( new Action( Action::ACTIVATE ) );
		$this->behavior( new Action( Action::UPDATE ) );

		// CRUD Operations
	    $this->behavior( new Action( Action::CREATE ) );
	    $this->behavior( new Action( Action::READ ) );
	    $this->behavior( new Action( Action::DELETE ) );
	    $this->behavior( new Action( Action::SAVE ) );

	    // User Interactions
	    $this->behavior( new Action( Action::CLICK ) );
	    $this->behavior( new Action( Action::HOVER ) );
	    $this->behavior( new Action( Action::SCROLL ) );
	    $this->behavior( new Action( Action::INPUT ) );

	    // System and Application
	    $this->behavior( new Action( Action::RUN ) );
	    $this->behavior( new Action( Action::START ) );
	    $this->behavior( new Action( Action::STOP ) );
	    $this->behavior( new Action( Action::RESTART ) );
	    $this->behavior( new Action( Action::PAUSE ) );
	    $this->behavior( new Action( Action::RESUME ) );

	    // Network and Communication
	    $this->behavior( new Action( Action::CONNECT ) );
	    $this->behavior( new Action( Action::DISCONNECT ) );
	    $this->behavior( new Action( Action::SEND ) );
	    $this->behavior( new Action( Action::RECEIVE ) );
	    $this->behavior( new Action( Action::SYNC ) );

	    // Authentication
	    $this->behavior( new Action( Action::LOGIN ) );
	    $this->behavior( new Action( Action::LOGOUT ) );
	    $this->behavior( new Action( Action::AUTHENTICATE ) );
	    $this->behavior( new Action( Action::AUTHORIZE ) );

	    // Error handling
	    $this->behavior( new Action( Action::THROW_ERROR ) );
	    $this->behavior( new Action( Action::CATCH_ERROR ) );
	    $this->behavior( new Action( Action::HANDLE_EXCEPTION ) );

	    // Data manipulation and validation
	    $this->behavior( new Action( Action::VALIDATE ) );
	    $this->behavior( new Action( Action::FILTER ) );
	    $this->behavior( new Action( Action::TRANSFORM ) );

	    // Application specific actions
	    $this->behavior( new Action( Action::PROCESS ) );
	    $this->behavior( new Action( Action::REFRESH ) );
	    $this->behavior( new Action( Action::LOAD_MORE ) );
	}
}