<?php

namespace BlueFission\Data\Queues;

use RuntimeException;
use BlueFission\Collections\Collection;

/**
 * Class FileQueue
 * 
 * @package BlueFission\Data\Queues
 * @implements IQueue
 */
class FileQueue extends Queue implements IQueue {
    const FILENAME = 'file_queue_stack.tmp';

    /**
     * File handle for the queue file.
     */
    private static $handle = null;

    /**
     * Cache of the queue data.
     */
    private static $cache = [];

    private function __construct() {}

    private function __clone() {}

    /**
     * Ensures that the file handle is opened and ready for use.
     */
    private static function ensureFileHandle() {
        if (self::$handle === null) {
            $tempfile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . self::FILENAME;

            // Try to open file handle
            self::$handle = fopen($tempfile, 'c+');
            if (self::$handle === false) {
                throw new RuntimeException("Unable to open queue file.");
            }
            // Acquire an exclusive lock
            if (!flock(self::$handle, LOCK_EX)) {
                fclose(self::$handle);
                self::$handle = null;
                throw new RuntimeException("Unable to lock queue file.");
            }
            // Read existing data into cache
            $data = stream_get_contents(self::$handle);
            self::$cache = $data ? unserialize($data) : [];
        }
    }

    public static function isEmpty($queue) {
        self::ensureFileHandle();
        return empty(self::$cache[$queue]);
    }

    public static function dequeue($queue, $after_id=false, $till_id=false) {
        self::ensureFileHandle();

        // Reverse the array if FILO
        if ( self::$_mode == static::FILO ) {
        	self::$cache[$queue] = array_reverse(self::$cache[$queue]);
        }

        if ( $after_id === false && $till_id === false ) {
        	$item = array_shift(self::$cache[$queue]);
        } else {
            $after_id = $after_id ?? 0;
            $length = $till_id === false ? count(self::$cache[$queue]) : $till_id;
            $item = new Collection(array_splice(self::$cache[$queue], $after_id, $length));
        }

        // Fix it
        if ( self::$_mode == static::FILO ) {
        	self::$cache[$queue] = array_reverse(self::$cache[$queue]);
        }
        self::save();
        return $item;
    }

    public static function enqueue($queue, $item) {
        self::ensureFileHandle();
        self::$cache[$queue][] = $item;
        self::save();
    }

    /**
     * Writes the current cache back to the file.
     */
    private static function save() {
        if (self::$handle === null) {
            throw new RuntimeException("File handle is not open.");
        }
        // Truncate the file and write serialized data
        ftruncate(self::$handle, 0);
        rewind(self::$handle);
        fwrite(self::$handle, serialize(self::$cache));
        fflush(self::$handle); // flush output before releasing the lock
    }

    /**
     * Closes the file handle.
     */
    public static function close() {
        if (self::$handle !== null) {
            flock(self::$handle, LOCK_UN);
            fclose(self::$handle);
            self::$handle = null;
        }
    }

    public function __destruct() {
        self::close();
    }
}
