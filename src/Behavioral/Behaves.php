<?php
namespace BlueFission\Behavioral;

use BlueFission\DevValue;
use BlueFission\DevObject;
use BlueFission\Collections\Collection;
use BlueFission\Exceptions\NotImplementedException;
use BlueFission\Behavioral\Behaviors\Behavior;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Action;
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
        Dispatches::init as private dispatchesInit;
    }
    /**
     * Collection to store history of performed behaviors.
     *
     * @var Collection
     */
    protected $_history;

    /**
     * Collection to store the current state of the object.
     *
     * @var Collection
     */
    protected $_state;

    /**
     * Determines whether the object can have multiple states at once.
     *
     * @var bool
     */
    protected $_multistate = true;

    /**
     * Behavioral constructor.
     */
    public function __construct()
    {
        $this->_history = new Collection();
        $this->_state = new Collection();

        $this->__dispatchesConstruct();

        $this->perform( State::DRAFT );
    }

    /**
     * Performs a behavior on the object.
     *
     * @param string|Behavior $behavior The behavior to perform.
     * @throws InvalidArgumentException If an invalid behavior type is passed.
     * @throws NotImplementedException If the behavior is not implemented.
     */
    public function perform( )
    {
        $args = func_get_args();
        $behavior = array_shift( $args );

        if ( is_string($behavior) )
            $behaviorName = $behavior;
        elseif ( !($behavior instanceof Behavior) )
            throw new InvalidArgumentException("Invalid Behavior Type");
        else
            $behaviorName = $behavior->name();

        if ( $this->can( $behaviorName ) )
        {
            $behavior = ($behavior instanceof Behavior) ? $behavior : $this->_behaviors->get($behaviorName);

            if (!$behavior) return;
            
            if ($behavior->_target == null) {
                $behavior->_target = $this;
            }

            if ($behavior->_context == null) {
                $behavior->_context = $args;
            }

            $this->dispatch( $behavior, $args );
            $this->_history->add($behaviorName, $args ? $args : $behaviorName);
            if ( $this->_behaviors->has( $behaviorName ) && $this->_behaviors->get( $behaviorName )->is_persistent() )
            {
                if ( !$this->_multistate )
                    $this->_state->clear();
                $this->_state->add($behaviorName, $args ? $args : $behaviorName);
            }
		}
		else
		{
			throw new NotImplementedException("Behavior '{$behaviorName}' is not implemented");
		}
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
		$can = ( ( $this->_behaviors->has( $behaviorName ) || $this->is( State::DRAFT ) ) && !$this->is( State::BUSY ) );
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
			return $this->_state->has( $behaviorName );
		} else {
			return $this->_state->last();
		}
	}

	/**
	 * Halt the specified behavior.
	 * 
	 * @param string $behaviorName The name of the behavior to halt.
	 */
	public function halt( $behaviorName )
	{
		$this->_state->remove( $behaviorName );
	}

	/**
	 * Initialize the object.
	 */
	protected function init()
	{
		$this->dispatchesInit();
		$this->behavior( new Event( Event::CHANGE ) );
		$this->behavior( new Event( Event::ACTIVATED ) );
		$this->behavior( new Event( Event::COMPLETE ) );
		$this->behavior( new Event( Event::SUCCESS ) );
		$this->behavior( new Event( Event::FAILURE ) );

		$this->behavior( new State( State::DRAFT ) );
		$this->behavior( new State( State::DONE ) );
		$this->behavior( new State( State::NORMAL ) );
		$this->behavior( new State( State::READONLY ) );
		$this->behavior( new State( State::BUSY ) );

		$this->behavior( new Action( Action::ACTIVATE ) );
		$this->behavior( new Action( Action::UPDATE ) );
	}
}