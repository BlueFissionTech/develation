<?php

namespace BlueFission\Connections;

use BlueFission\Arr;
use BlueFission\Obj;
use BlueFission\IObj;
use BlueFission\Behavioral\IConfigurable;
use BlueFission\Behavioral\Configurable;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Meta;

/**
 * Class Connection
 *
 * An abstract class that defines the structure for database connections.
 */
abstract class Connection extends Obj implements IConfigurable
{
    use Configurable {
        Configurable::__construct as private __configConstruct;
    }
    /**
     * Connection resource
     *
     * @var resource|null
     */
    protected $_connection = null;

    /**
     * Query result
     *
     * @var mixed|null
     */
    protected $_result = null;

    /**
     * Constant for connected status
     */
    public const STATUS_CONNECTED = 'Connected.';

    /**
     * Constant for not connected status
     */
    public const STATUS_NOTCONNECTED = 'Not Connected.';

    /**
     * Constant for disconnected status
     */
    public const STATUS_DISCONNECTED = 'Disconnected.';

    /**
     * Constant for success status
     */
    public const STATUS_SUCCESS = 'Query success.';

    /**
     * Constant for failed status
     */
    public const STATUS_FAILED = 'Query failed.';

    /**
     * Connection constructor
     *
     * @param array|null $config
     */
    public function __construct($config = null)
    {
        $this->__configConstruct();
        parent::__construct();

        if (Arr::is($config)) {
            $this->config($config);
        }

        $this->status(self::STATUS_NOTCONNECTED);

        $this->behavior(new Action(Action::CONNECT), function ($behavior) {
            $this->open();
        });
        $this->behavior(new Action(Action::DISCONNECT), function ($behavior) {
            $this->close();
        });
        $this->behavior(new Action(Action::PROCESS), function ($behavior) {
            $this->query();
        });

        $this->behavior(new Event(Event::SUCCESS), function ($behavior, $args) {
            $this->status(self::STATUS_SUCCESS);

            $args = $args ?? $behavior->context;
            $action = '';

            if ($args && $args instanceof Meta) {
                $action = $args?->when?->name();
            }

            if (Action::CONNECT == $action && $this->is(State::CONNECTING)) {
                $this->status(self::STATUS_CONNECTED);
                $this->perform(Event::CONNECTED);
            }
            if (Action::DISCONNECT == $action && $this->is(State::DISCONNECTING)) {
                $this->status(self::STATUS_DISCONNECTED);
                $this->perform(Event::DISCONNECTED);
            }
            if (Action::PROCESS == $action && $this->is(State::PROCESSING)) {
                $this->perform(Event::PROCESSED);
            }

            if ($action) {
                $this->trigger(Event::ACTION_PERFORMED, $action);
            }
        });

        $this->behavior(new Event(Event::FAILURE), function ($behavior, $args) {
            $args = $args ?? $behavior->context;
            $action = '';

            if ($args && $args instanceof Meta) {
                $action = $args?->when?->name();
            }

            if (Action::CONNECT == $action && $this->is(State::CONNECTING)) {
                $this->halt(State::CONNECTING);
            }
            if (Action::DISCONNECT == $action && $this->is(State::DISCONNECTING)) {
                $this->halt(State::DISCONNECTING);
            }
            if (Action::PROCESS == $action && $this->is(State::PROCESSING)) {
                $this->halt(State::PROCESSING);
            }

            if ($action) {
                $this->trigger(Event::ACTION_FAILED, $action);
            }
        });
    }

    /**
     * Abstract method to open a connection
     */
    public function open(): IObj
    {
        $this->perform(State::PERFORMING_ACTION, new Meta(when: Action::CONNECT)) ;
        $this->perform(State::CONNECTING);

        if (method_exists($this, '_open')) {
            $this->_open();
        }

        $this->halt(State::CONNECTING);

        return $this;
    }

    /**
     * Close the connection
     */
    public function close(): IObj
    {
        $this->perform(State::PERFORMING_ACTION, new Meta(when: Action::DISCONNECT)) ;
        $this->perform(State::DISCONNECTING);

        if (method_exists($this, '_close')) {
            $this->_close();
        }

        if ($this->is(State::DISCONNECTED)) {
            $this->_connection = null;
            $this->status(self::STATUS_NOTCONNECTED);
        }

        $this->halt(State::DISCONNECTING);


        return $this;
    }

    /**
     * Abstract method to run a query on the connection
     *
     * @param string|null $query
     */
    abstract public function query($query = null);

    /**
     * Get the result of a query
     *
     * @return mixed|null
     */
    public function result()
    {
        return $this->_result;
    }

    public function connection()
    {
        return $this->_connection;
    }
}
