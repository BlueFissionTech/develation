<?php
namespace BlueFission\Data\Queues;

use Memcached;
use BlueFission\Collections\Collection;

/**
 * Class MemQueue
 * 
 * This class is an implementation of a queue using Memcached and based on MEMQ (https://github.com/abhinavsingh/memq)
 * 
 * @link http://abhinavsingh.com/memq-fast-queue-implementation-using-memcached-and-php-only/
 */
class MemQueue extends Queue implements IQueue
{
    /**
     * Stores the Memcached instance
     *
     * @var Memcached|null
     */
    private static $stack = NULL;

    /**
     * The default pool to be used for Memcached
     */
    private static $memqPool = 'localhost:11211';

    /**
     * The default time-to-live value for items in the queue
     */
    const MEMQ_TTL = 0;
    
    /**
     * Prevents creating an instance of the class
     */
    private function __construct() {}
    
    /**
     * Prevents cloning of the class instance
     */
    private function __clone() {}
    
    /**
     * Returns the Memcached instance
     *
     * @return Memcached
     */
    private static function instance() {
        if (!self::$stack) self::init();
        return self::$stack;
    }

    /**
     * Sets the Memory pool address
     * 
     * @param string $pool The address of the Memcached pool
     */
    public function setPool($pool) {
        self::$memqPool = $pool;
    }
    
    /**
     * Initializes the Memcached instance
     *
     * @return void
     */
    private static function init() {
        $stack = new Memcached;
        $servers = explode(",", static::$memqPool);
        foreach ($servers as $server) {
            list($host, $port) = explode(":", $server);
            $stack->addServer($host, $port);
        }
        self::$stack = $stack;
    }
    
    /**
     * Determines if the queue is empty
     *
     * @param string $queue
     * @return bool
     */
    public static function isEmpty($queue) {
        $stack = self::instance();
        $head = $stack->get($queue . "_head");
        $tail = $stack->get($queue . "_tail");
            
        if ($head === false || $tail === false || $head >= $tail) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Dequeues an item from the queue
     *
     * @param string $queue
     * @param int|bool $after
     * @param int|bool $until
     * @return mixed
     */
    public static function dequeue($queue, $after = false, $until = false) {
        $stack = self::instance();

        if ($after === false && $until === false) {
            if (self::$mode == static::FIFO) {
                $tail = $stack->get($queue . "_tail");

                if (($id = $stack->increment($queue . "_head")) === false) {
                    return false;
                }

                if ($id <= $tail) {
                    $output = $stack->get($queue . "_" . ($id - 1));
                    $stack->delete($queue . "_" . ($id - 1));
                    return $output;
                } else {
                    $stack->decrement($queue . "_head");
                    return false;
                }
            } elseif (self::$mode == static::FILO) {
                $head = $stack->get($queue . "_head");

                if (($id = $stack->decrement($queue . "_tail")) === false) {
                    return false;
                }

                if ($id > $head) {
                    $output = $stack->get($queue . "_" . ($id - 1));
                    $stack->delete($queue . "_" . ($id - 1));
                    return $output;
                } else {
                    $stack->increment($queue . "_tail");
                    return false;
                }
            }
        } else if ($after !== false && $until === false) {
            $until = $stack->get($queue . "_tail");
        }

        $item_keys = [];
        for ($i = $after + 1; $i <= $until; $i++) {
            $item_keys[] = $queue . "_" . $i;
        }

        $items = $stack->getMulti($item_keys, Memcached::GET_PRESERVE_ORDER);
        
        if ($items === false || empty($items)) {
            return false;
        }

        foreach ($item_keys as $key) {
            $stack->delete($key);
        }

        return new Collection($items);
    }
    
    /**
     * Enqueue a new item in the specified queue
     *
     * @param string $queue The name of the queue to add the item to
     * @param mixed $item The item to add to the queue
     *
     * @return int|bool The ID of the added item or FALSE on failure
     */
    public static function enqueue($queue, $item) {
        $stack = self::instance();
        
        $id = $stack->increment($queue . "_tail");
        if ($id === false) {
            if ($stack->add($queue . "_tail", 1, self::MEMQ_TTL) === false) {
                $id = $stack->increment($queue . "_tail");
                if ($id === false) {
                    return false;
                }
            } else {
                $id = 1;
                $stack->add($queue . "_head", 0, self::MEMQ_TTL);
            }
        }
        
        if ($stack->add($queue . "_" . $id, $item, self::MEMQ_TTL) === false) {
            return false;
        }
        
        return $id;
    }
}
?>
