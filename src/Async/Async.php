<?php
namespace BlueFission\Async;

use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaves;
use BlueFission\Behavioral\IBehavioral;
use BlueFission\Data\Queues\IQueue;
use BlueFission\Data\Queues\SplPriorityQueue;
use BlueFission\Data\Log;
use BlueFission\IObj;
use BlueFission\Obj;
use BlueFission\Arr;

/**
 * The Async class provides a framework for executing asynchronous tasks using a behavioral pattern.
 * It allows tasks to be queued and executed without blocking the main thread of execution.
 */
abstract class Async extends Obj implements IAsync, IObj, IBehavioral {
    use Behaves {
        Behaves::__construct as private __behavesConstruct;
    }

    /**
     * Singleton instance of the Async class.
     */
    private static $_instance = null;

    /**
     * Queue that holds all the tasks to be executed asynchronously.
     */
    protected static $_tasks;

    /**
     * Configuration settings
     */
    protected static $_config = [];

    /**
     * Queue implementation used for storing tasks.
     */
    protected static $_queue;

    protected static $_queueName = 'async_queue';


    protected static $_time;

    /**
     * Private constructor to prevent creating a new instance outside of the class.
     */
    private function __construct() {
        parent::__construct();
        $this->__behavesConstruct(); // Initialize behavioral traits

        // Registering events for the lifecycle of the asynchronous process
        $this->behavior(new Event(Event::LOAD));
        $this->behavior(new Event(Event::UNLOAD));
        $this->behavior(new Event(Event::COMPLETE));
        $this->behavior(new Event(Event::ERROR));

        // Initializing the task queue
        self::$_tasks = self::getQueue();
        self::$_config = self::getConfig();
    }

    /**
     * Sets the queue implementation to be used for task management.
     * 
     * @param IQueue $_queueClass Instance of a queue class implementing the IQueue interface.
     */
    public static function setQueue(string $_queueClass) {
        self::$_queue = $_queueClass;
    }

    /**
     * Returns the task queue.
     */
    private function tasks() {
        return self::$_tasks;
    }

    /**
     * Returns the queue instance, initializing it if necessary.
     */
    protected static function getQueue(): string {
        if (!self::$_queue) {
            self::$_queue = SplPriorityQueue::class; // Default to SplPriorityQueue if no custom queue provided
        }
        return self::$_queue;
    }

    public static function setConfig(array $_config)
    {
        self::$_config = $_config;
    }

    protected static function getConfig(): array
    {
        return Arr::merge(self::$_config, [
            'max_concurrency' => 10,
            'default_timeout' => 30,
            'retry_strategy' => 'simple',
            'timeout' => 300,
            'notifyURL' => 'http://localhost:8080',
        ]);
    }

    /**
     * Provides access to the singleton instance of the Async class.
     */
    protected static function instance() {
        if (self::$_instance === null) {
            self::$_instance = new static();
            self::$_instance->perform(Event::INITIALIZED);
        }
        return self::$_instance;
    }

    /**
     * Executes a function asynchronously.
     * 
     * @param callable $_function The function to execute.
     * @return Async The instance of the Async class.
     */
    public static function exec($_function, $_priority = 10) {
        $_instance = self::instance();
        $_instance->perform(State::PROCESSING);
        $_promise = new Promise($_function, $_instance);

        self::keep($_promise, $_priority);

        return $_promise;
    }

    public static function keep( $_promise, $_priority = 10 )
    {
        $_instance = self::instance();

        $_instance->tasks()::enqueue([
            'data'=>$_instance->wrapPromise($_promise), 
            'priority'=>$_priority
        ], self::$_queueName);
    }

    /**
     * Wraps a function within a generator to manage execution flow.
     * 
     * @param callable $_function The function to wrap.
     * @return callable A generator function.
     */
    protected function wrapPromise($_promise) {
        return function() use ($_promise) {
            $_result = $this->executePromise($_promise);
            foreach ($_result as $value) {
                yield $value;
            }
        };
    }

    /**
     * Execute the provided function, intended to be overridden in subclasses for custom behavior.
     *
     * @param callable $_function The function to execute.
     * @return \Generator Yields the function's result, handles success or failure internally.
     */
    protected function executePromise($_promise) {
        try {
            $_result = $_promise->try();
            if (!($_result instanceof \Generator)) {
                yield $_result;
            } else {
                yield from $_result;
            }
            $this->perform(Event::SUCCESS);
        } catch (TransientException $_e) {
            error_log('Transient exception: ' . $_e->getMessage());
            $this->perform(Event::ERROR, $_e->getMessage());
            $this->status($_e->getMessage());

            $this->retry($_promise->try());
        } catch (\Exception $_e) {
            yield $this->handleError('Unhandled exception: ' . $_e);
        }
    }

    protected function retry( $_function )
    {
        $_function();
    }

    protected function handleError(\Exception $_e) {
        $this->logError($_e); // Log the error or perform other error reporting.
        $this->perform([Event::Error, Event::FAILURE], new Meta(info: $_e->getMessage()));

        return null;
    }

    protected function monitorStart($_task) {
        // Logic to log or monitor the start of a task, could include timing.
        self::$_time = time();
    }

    protected function monitorEnd($_task) {
        // Logic to log or monitor the end of a task, could include timing and result status.
        $_time = time() - self::$_time;
    }

    protected function logError(\Exception $_e) {
        // Log the error using a logging system or error reporting service.
        error_log($_e);
    }

    protected function checkTimeout($_task) {
        // Implement timeout check
        if (time() - $_task['start_time'] > self::getConfig()['task_timeout']) {
            throw new TimeoutException("Task timed out");
        }
    }

    protected function notifyCompletion($_data) {
        $_context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: text/plain",
                'content' => json_encode($_data)
            ]
        ]);

        file_get_contents(self::getConfig()['notifyURL'], false, $_context);
    }

    /**
     * Runs all queued tasks.
     */
    public static function run() {
        $_instance = self::instance();

        if ($_instance->is(State::RUNNING))
            return;

        $_instance->perform(Event::STARTED);
        $_instance->perform(State::RUNNING);

        while (!$_instance->tasks()::isEmpty(self::$_queueName)) {

            $_task = $_instance->tasks()::dequeue(self::$_queueName);

            $_instance->monitorStart($_task);
            $_generator = $_task();
            while ($_generator->valid()) {
                if ($_generator->current() === null) {
                    $_instance->perform(Event::FAILURE);
                    break;
                }
                $_generator->next();
            }
            $_instance->monitorEnd($_task);
            // $_instance->notifyCompletion(['message' => Event::COMPLETE, 'result' => $_result]);
            $_instance->perform(Event::PROCESSED);
        }

        $_instance->halt(State::RUNNING);
        $_instance->perform(Event::COMPLETE);
        $_instance->perform(Event::STOPPED);
        $_instance->halt(State::PROCESSING);
    }

    /**
     * Destructor method to ensure all tasks are run and resources are cleaned up.
     */
    public function __destruct() {
        try {
            $this->perform(State::FINALIZING);
            self::run();
            $this->perform(Event::FINALIZED);
        } catch (\Exception $_e) {
            $this->perform(Event::ERROR, new Meta(info: $_e->getMessage()));
            $this->perform(State::ERROR_STATE);
        }
        $this->perform(Event::UNLOAD);
    }
}
