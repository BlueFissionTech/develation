<?php

namespace BlueFission;

// This class, which will be embedded in every IVal and IObj class, including those that use Behaves, Configurable, or Programmable or implement their interfaces
// Will be sent every fired event and hold configuration setting overrides for most IConfigurable objects. Because it can be hard to run code or test with
// Global state object, especially those that prevail over the project as much as this one, it is turned off by default and must be activated before it does
// any work. It can also be memory intensive in large projects where the developer might prefer a custom solution to those problems. It will also have the ability
// to register hooks, actions, and filters throughout the code. for instance, there might be a hook to filter all outgoing data with a prefix or as a JSON object.
// That would be done through `DevElation::filter('filter_name', $filterFunction, $priority);` where filter names are mostly all dynamic but predictable.
// Imagine that, for instance, adding `$output = DevElation::apply('filter_name', $input);` applies the filter 'filter_name', but 
// `$output = DevElation::apply(null, $input);` instead, because the name is null, would automatically generate a filter name based on the class and method, so
// `Builder::makeItem();` would have a filter name of 'builder.make_item'. Actions would work the same way, which won't produce an output, just trigger some action through
// `DevElation::do('action_name', $input);` and `DevElation::action('action_name', function( $input ) {}, $priority);`. There would also be the ability to subscribe to
// events through this class. For example, from the originating class `DevElation::listen($eventOrBehavior);` and in the target class 
// `DevElation::subscribe($this, $eventOrBehavior);` now registers that item as a listener of that event through `Dispatches::trigger($eventName, $args);`

class DevElation {
    private static $_isActive = false;
    private static $_config = [];
    private static $_filters = [];
    private static $_actions = [];
    private static $_listeners = [];

    const BACKTRACE_DEPTH = 3; // Adjust this depth as needed
    const CALLER_INDEX = 2; // Adjust this index based on your backtrace structure

    public static function up()
    {
        // Activate the class
        self::$_isActive = true;
    }

    public static function down()
    {
        // Deactivate the class
        self::$_isActive = false;
    }

    public static function config($key = null, $value = null)
    {
    	// Automatically determine class name if key is not provided
        if (func_num_args() == 0) {
            $key = self::getCallerClassName();
        }

        if ($value === null) {
            return self::$_config[$key] ?? null;
        } else {
            self::$_config[$key] = $value;
        }
    }

    public static function filter($name, callable $function, $priority = 10)
    {
        if (!isset(self::$_filters[$name])) {
            self::$_filters[$name] = [];
        }
        self::$_filters[$name][$priority][] = $function;
        ksort(self::$_filters[$name]); // Sort by priority
    }

    public static function apply($name = null, $value = null)
    {
    	$name = self::generateHookName($name);
        if (!self::$_isActive || !isset(self::$_filters[$name])) {
            return $value;
        }
        foreach (self::$_filters[$name] as $priority => $filters) {
            foreach ($filters as $filter) {
                $value = $filter($value);
            }
        }
        return $value;
    }

    public static function action($name, callable $function, $priority = 10)
    {
        if (!isset(self::$_actions[$name])) {
            self::$_actions[$name] = [];
        }
        self::$_actions[$name][$priority][] = $function;
        ksort(self::$_actions[$name]); // Sort by priority
    }

    public static function do($name = null, $args = [])
    {
    	$name = self::generateHookName($name);
        if (!self::$_isActive || !isset(self::$_actions[$name])) {
            return;
        }
        foreach (self::$_actions[$name] as $priority => $actions) {
            foreach ($actions as $action) {
                call_user_func_array($action, $args);
            }
        }
    }

    public static function listen($eventOrBehavior)
    {
        if (!isset(self::$_listeners[$eventOrBehavior])) {
            self::$_listeners[$eventOrBehavior] = [];
        }
    }

    public static function subscribe($subscriber, $eventOrBehavior)
    {
        if (isset(self::$_listeners[$eventOrBehavior]) && !in_array($subscriber, self::$_listeners[$eventOrBehavior])) {
            self::$_listeners[$eventOrBehavior][] = $subscriber;
        }
    }

    public static function trigger($eventName, $args = [])
    {
        if (!self::$_isActive || !isset(self::$_listeners[$eventName])) {
            return;
        }
        foreach (self::$_listeners[$eventName] as $listener) {
            // Assuming $listener is callable or has a method to handle the event
            call_user_func_array($listener, $args);
        }
    }

    private static function generateHookName($name = null)
    {
        if ($name && strpos($name, '_') !== 0) {
            return $name;
        }

        $append = '';
        if ($name !== null && strpos($name, '_') === 0) {
            $append = '.'.substr($name, 1);
        }

        $name = '';
        
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, self::BACKTRACE_DEPTH);
        $caller = $backtrace[self::CALLER_INDEX] ?? null;

        if (isset($caller['class']) && isset($caller['function'])) {
            $class = str_replace(__NAMESPACE__ . '\\', '', $caller['class']);
            $function = $caller['function'];
            $name = strtolower($class . '.' . $function) . ($append ?? '');
            return $name;
        } elseif (isset($caller['function'])) {
            $function = $caller['function'];
            $name = strtolower($function) . ($append ?? '');
            return $name;
        }

        return 'global.hook';
    }
}
