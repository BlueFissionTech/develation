<?php
namespace BlueFission\Data\Queues;

use BlueFission\Data\Storage\Storage;
use BlueFission\Data\Storage\MySql;

class DBQueue extends Queue implements IQueue {
    /**
     * @var Storage $storage Static storage handler.
     */
    private static $_storage;

    /**
     * Set the storage handler for the queue.
     * The storage should be an instance of a class that extends Storage.
     *
     * @param Storage $storage A storage instance that implements the Storage interface.
     */
    public static function setStorage(Storage $storage) {
        self::$_storage = $storage;
        self::$_storage->activate(); // Ensure storage is activated and ready to use.
    }

    private static function storage()
    {
        if ( self::$_storage && is_a(self::$_storage, 'BlueFission\Data\Storage\Storage') ) {
            return self::$_storage;
        }

        self::$_storage = new MySql([
            'location'=>null,
            'name'=>'queue_'.uniqid(),
            'fields'=>['message_id', 'channel', 'message'],
            'ignore_null'=>false,
            'auto_join'=>true,
            'save_related_tables'=>false,
            'temporary'=>true,
            'set_defaults'=>false,
            'key'=>'message_id',
        ]);
        self::$_storage->activate(); // Ensure storage is activated and ready to use.

        return self::$_storage;
    }

    /**
     * Check if the queue is empty.
     *
     * @param string $queue Name of the queue
     *
     * @return bool True if empty, False otherwise.
     */
    public static function isEmpty($queue) {
        $storage = self::storage();

        $storage->channel = $queue;
        return $storage->clear()->read()->id() ? false : true;
    }

    /**
     * Enqueue an item to the queue.
     *
     * @param string $queue Name of the queue
     * @param mixed $item Item to be added to the queue
     *
     * @return bool Returns true if successful, false on failure
     */
    public static function enqueue($queue, $item) {
        $storage = self::storage();
        
        $storage->channel = $queue;
        $storage->message = serialize($item);
        $storage->write();
        return true;
    }

    /**
     * Dequeue an item from the queue.
     *
     * @param string $queue Name of the queue
     *
     * @return mixed Returns the dequeued item or null if the queue is empty or on error
     */
    public static function dequeue($queue, $after_id = false, $till_id = false) {
        $storage = self::storage();
        
        // Ensuring FIFO order
        $storage->clear()->order('message_id', 'ASC')->channel = $queue;;
        $item = $storage->read()->message;
        if ($item !== null) {
            $item = unserialize($item);
            if ( $storage->id() ) {
                $storage->delete(); // Remove the message from storage after reading
            }
            $storage->clear();
            return $item;
        }
        return null;
    }
}
