<?php
namespace BlueFission\Data;

use BlueFission\IObj;
use BlueFission\Obj;
use BlueFission\Behavioral\Configurable;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Meta;

/**
 * Class Data
 *
 * @package BlueFission\Data
 * 
 * The Data class extends the Obj class and implements the IData interface.
 * This class is used to manage data objects and their properties.
 */
class Data extends Obj implements IData
{
    use Configurable {
        Configurable::__construct as private __configConstruct;
    }

    public function __construct( $config = null )
    {
        parent::__construct();
        $this->__configConstruct($config);

        $this->behavior(new Action( Action::CREATE ), function($behavior) {
            $this->write();
        });
        $this->behavior(new Action( Action::UPDATE ), function($behavior) {
            $this->write();
        });
        $this->behavior(new Action( Action::SAVE ), function($behavior) {
            $this->write();
        });
        $this->behavior(new Action( Action::READ ), function($behavior) {
            $this->read();
        });
        $this->behavior(new Action( Action::DELETE ), function($behavior) {
            $this->delete();
        });

        $this->behavior(new Event( Event::SUCCESS ), function($behavior, $args) {
            $args = $args ?? $behavior->context;
            $action = '';

            if ($args && $args instanceof Meta ) {
                $action = $args?->when?->name();
            }

            if ( Action::READ == $action && $this->is(State::READING) ) {
                $this->perform(Event::READ);
            }
            if ( Action::CREATE == $action && $this->is(State::CREATING) ) {
                $this->perform(Event::CREATED);
            }
            if ( Action::UPDATE == $action && $this->is(State::UPDATING) ) {
                $this->perform(Event::UPDATED);
            }
            if ( Action::DELETE == $action && $this->is(State::DELETING) ) {
                $this->perform(Event::DELETED);
            }
            if ( Action::SAVE == $action && $this->is(State::SAVING) ) {
                $this->perform(Event::SAVED);
            }

            if ( $action ) {
                $this->trigger( Event::ACTION_PERFORMED, $action );
            }
        });

        $this->behavior(new Event( Event::FAILURE ), function($behavior, $args) {
            $args = $args ?? $behavior->context;
            $action = '';
            
            if ($args && $args instanceof Meta ) {
                $action = $args?->when?->name();
            }

            if ( Action::READ == $action && $this->is(State::READING) ) {
                $this->halt(State::READING);
            }
            if ( Action::CREATE == $action && $this->is(State::CREATING) ) {
                $this->halt(State::CREATING);
            }
            if ( Action::UPDATE == $action && $this->is(State::UPDATING) ) {
                $this->halt(State::SAVING);
                $this->halt(State::UPDATING);
            }
            if ( Action::DELETE == $action && $this->is(State::DELETING) ) {
                $this->halt(State::SAVING);
                $this->halt(State::DELETING);
            }
            if ( Action::SAVE == $action && $this->is(State::SAVING) ) {
                $this->halt(State::SAVING);
            }

            if ( $action ) {
                $this->trigger( Event::ACTION_FAILED, $action );
            }
        });
    }
    
    /**
     * This method is used to read data.
     *
     * @return IObj
     */
    public function read(): IObj 
    {
        // method implementation
        $this->perform( State::PERFORMING_ACTION, new Meta(when: Action::READ) );
        $this->perform( State::READING );

        if ( method_exists($this, '_read') ) {
            $this->_read();
        }

        $this->halt( State::READING );

        return $this;
    }
    
    /**
     * This method is used to write data.
     *
     * @return IObj
     */
    public function write(): IObj 
    {
        // method implementation
        $this->perform( State::PERFORMING_ACTION, new Meta(when: Action::SAVE)  );
        $this->perform( State::SAVING );

        if ( method_exists($this, '_write') ) {
            $this->_write();
        }

        $this->halt( State::SAVING );

        return $this;
    }
    
    /**
     * This method is used to delete data.
     *
     * @return IObj
     */
    public function delete(): IObj 
    {
        // method implementation
        $this->perform( State::PERFORMING_ACTION, new Meta(when: Action::DELETE)  ) ;
        $this->perform( State::DELETING );

        if ( method_exists($this, '_delete') ) {
            $this->_delete();
        }

        $this->halt( State::DELETING );

        return $this;
    }
    
    /**
     * This method is used to get the contents of data.
     *
     * @return mixed
     */
    public function contents($data = null): mixed
    {
        // method implementation
        if ( method_exists($this, '_contents') ) {
            $data = $this->_contents($data);
        }
        
        return $data;
    }
    
    /**
     * This method is used to register the input variables from various sources as global variables.
     *
     * @param string $source
     *
     * @return IObj
     */
    public function registerGlobals( string $source = null ): IObj
    {
        $source = strtolower($source);
        switch( $source )
        {
            case 'post':
                $vars = filter_input_array(INPUT_POST);
            break;
            case 'get':
                $vars =  filter_input_array(INPUT_GET);
            break;
            case 'session':
                $vars = filter_input_array(INPUT_SESSION);
            break;
            case 'cookie':
            case 'cookies':
                $vars = filter_input_array(INPUT_COOKIE);
            break;
            default:
            case 'globals':
                $vars = $GLOBALS;
            break;
            case 'request':
                $vars = filter_input_array(INPUT_REQUEST);
            break;
        }

        $this->assign($vars);

        return $this;
    }    
}