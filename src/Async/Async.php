<?php
namespace BlueFission\Async;

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
    protected static $_config;

    /**
     * Queue implementation used for storing tasks.
     */
    protected static $_queue;


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
     * @param IQueue $queueClass Instance of a queue class implementing the IQueue interface.
     */
    public static function setQueue(IQueue $queueClass) {
        self::$_queue = $queueClass;
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
    protected static function getQueue(): IQueue {
        if (!self::$_queue) {
            self::$_queue = new SplQueue(); // Default to SplQueue if no custom queue provided
        }
        return self::$_queue;
    }

    public static function setConfig(array $config)
    {
        self::$_config = $config;
    }

    protected static function getConfig(): array
    {
        return Arr::merge(self::$_config, [
            'max_concurrency' => 10,
            'default_timeout' => 30,
            'retry_strategy' => 'simple',
            'timeout' => 300,
        ]);
    }

    /**
     * Provides access to the singleton instance of the Async class.
     */
    private static function instance() {
        if (self::$_instance === null) {
            self::$_instance = new static();
            self::$_instance->perform(Event::INITIALIZED);
        }
        return self::$_instance;
    }

    /**
     * Executes a function asynchronously.
     * 
     * @param callable $function The function to execute.
     * @return Async The instance of the Async class.
     */
    public static function exec($function, $priority = 10) {
        $instance = self::instance();
        $instance->perform(State::PROCESSING);
        $promise = new Promise($function, $instance);
        $instance->tasks()->enqueue([
            'data'=>$instance->wrapFunction($function), 
            'priority'=>$priority
        ]);
        return $promise;
    }

    /**
     * Wraps a function within a generator to manage execution flow.
     * 
     * @param callable $function The function to wrap.
     * @return callable A generator function.
     */
    protected function wrapFunction($function) {
        return function() use ($function) {
            $result = $this->executeFunction($function);
            foreach ($result as $value) {
                yield $value;
            }
        };
    }

    /**
     * Execute the provided function, intended to be overridden in subclasses for custom behavior.
     *
     * @param callable $function The function to execute.
     * @return \Generator Yields the function's result, handles success or failure internally.
     */
    protected function executeFunction($function) {
        try {
            $result = $function();
            if (!($result instanceof \Generator)) {
                yield $result;
            } else {
                yield from $result;
            }
            $this->perform(Event::SUCCESS);
        } catch (TransientException $e) {
            $this->retry($function);
        } catch (\Exception $e) {
            yield $this->handleError($e);
        }
    }

    protected function retry( $function )
    {
        $function();
    }

    protected function handleError(\Exception $e) {
        $this->perform(Event::FAILURE, ['message' => $e->getMessage()]);
        $this->logError($e); // Log the error or perform other error reporting.
        return null;
    }

    protected function monitorStart($task) {
        // Logic to log or monitor the start of a task, could include timing.
        self::$_time = time();
    }

    protected function monitorEnd($task) {
        // Logic to log or monitor the end of a task, could include timing and result status.
        $time = time() - self::$_time;
    }

    protected function logError(\Exception $e) {
        // Log the error using a logging system or error reporting service.
        error_log($e)   ;
    }

    protected function checkTimeout($task) {
        // Implement timeout check
        if (time() - $task['start_time'] > $this->_config['task_timeout']) {
            throw new TimeoutException("Task timed out");
        }
    }

    protected function notifyCompletion($data) {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: text/plain",
                'content' => json_encode($data)
            ]
        ]);

        file_get_contents("http://localhost:8080", false, $context);
    }

    /**
     * Runs all queued tasks.
     */
    public static function run() {
        $instance = self::instance();
        $instance->perform(State::RUNNING);

        while (!$instance->tasks()->isEmpty()) {
            $task = $instance->tasks()->dequeue();
            $this->monitorStart($task);
            $generator = $task();
            while ($generator->valid()) {
                if ($generator->current() === null) {
                    $instance->perform(Event::FAILURE);
                    break;
                }
                $generator->next();
            }
            $this->monitorEnd($task);
            $instance->notifyCompletion(['message' => Event::COMPLETE, 'result' => $result]);
            $instance->perform(Event::PROCESSED);
        }

        $instance->halt(State::RUNNING);
        $instance->perform(Event::COMPLETE);
        $instance->halt(State::PROCESSING);
    }

    /**
     * Destructor method to ensure all tasks are run and resources are cleaned up.
     */
    public function __destruct() {
        try {
            $this->perform(State::FINALIZING);
            self::run();
            $this->perform(Event::FINALIZED);
        } catch (\Exception $e) {
            $this->perform(Event::ERROR, ['message' => $e->getMessage()]);
            $this->perform(State::ERROR_STATE);
        }
        $this->perform(Event::UNLOAD);
    }
}
