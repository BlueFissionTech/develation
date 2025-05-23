<?php

namespace BlueFission\Data\Queues;

use BlueFission\Arr;
use BlueFission\Data\FileSystem;
use BlueFission\Collections\Collection;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Action;

/**
 * Class DiskQueue
 * This class implements the IQueue interface and serves as a disk based queue for message storage and processing
 */
class DiskQueue extends Queue implements IQueue
{
    /**
     * Directory name for the disk-based queue
     * @var string
     */
    public const DIRNAME = 'php_temp_stack_dir';

    /**
     * Filename prefix for messages stored in the disk-based queue
     * @var string
     */
    public const FILENAME = 'message_';

    /**
     * The instance of the disk-based queue
     * @var string
     */
    private static $_stack;

    /**
     * The array of messages in the disk-based queue
     * @var array
     */
    private static $_array;

    private static $initialized = false;

    /**
     * Get the instance of the disk-based queue
     * @return string
     */
    private static function instance()
    {
        if (!self::$_stack) {
            self::init();
        }
        return self::$_stack;
    }

    /**
     * Initialize the disk-based queue by creating a directory and file system object
     * @return void
     */
    private static function init()
    {
        if (!self::$initialized) {
            $tempfile = sys_get_temp_dir();

            $stack = $tempfile.DIRECTORY_SEPARATOR.self::DIRNAME;

            $fs = new FileSystem(array('root' => $tempfile, 'mode' => 'a+', 'filter' => 'file', 'doNotConfirm' => true));

            if (file_exists($stack) && !is_dir($stack)) {
                unlink($stack);
            }

            if (!is_dir($stack)) {
                $fs->mkdir(self::DIRNAME);
            }

            self::$_stack = $stack;

            self::$initialized = true;
        }
    }

    /**
     * Check if the disk-based queue is empty
     * @param  string  $queue
     * @return boolean
     */
    public static function isEmpty($queue)
    {
        $stack = self::instance();

        $fs = new FileSystem(array('root' => $stack, 'mode' => 'r', 'filter' => 'file', 'doNotConfirm' => true, 'lock' => true));
        $fs->dirname = $queue;

        $array = $fs->listDir();

        if (!Arr::is($array)) {
            return true;
        }

        return count($array) ? false : true;
    }

    /**
     * Dequeue messages from the disk-based queue
     * @param  string  $queue
     * @param  integer $after
     * @param  integer $until
     * @return mixed
     */
    public static function dequeue($queue, $after = false, $until = false)
    {
        $stack = self::instance();

        $fs = new FileSystem(array('root' => $stack, 'mode' => 'a+', 'filter' => 'file', 'doNotConfirm' => true, 'lock' => true));
        $fs->dirname = $queue;
        $array = $fs->listDir();

        if ($array == false) {
            return false;
        }

        if (self::$_mode == static::FILO) {
            $array = array_reverse($array);
        }

        $message = null;

        if ($after === false && $until === false) {
            foreach ($array as $file) {
                $fs->basename = $file;
                $fs->open();

                if ($fs->isLocked()) {
                    $fs->read();
                    $message = $fs->contents();
                    $fs->delete();
                    $fs->close();

                    return $message ? unserialize($message) : null;
                }
            }
        } elseif ($after !== false && $until === false) {
            $until = self::tail($array);
        }

        $items = [];
        for ($i = $after + 1; $i <= $until; $i++) {
            $file = self::FILENAME . str_pad($i, 11, '0', STR_PAD_LEFT);
            $fs->filename = $file;

            if ($fs->exists()) {
                $fs->open();
            }

            if ($fs->isLocked()) {
                $fs->read();
                $message = $fs->contents();
                $fs->delete()->close();

                $items[] = $message ? unserialize($message) : null;
                $message = null;
            }
        }
        return new Collection($items);
    }

    /**
     * Adds a new item to the specified queue.
     *
     * @param string $queue The name of the queue to add the item to.
     * @param mixed $item The item to be added to the queue.
     *
     * @return void
     */
    public static function enqueue($queue, $item)
    {
        $stack = self::instance();

        $fs = new FileSystem(['root' => $stack, 'mode' => 'c', 'filter' => 'file', 'doNotConfirm' => true, 'lock' => true]);

        $fs->when(Event::CONNECTED, function () use ($fs) {
            $fs->write();
        })
        ->when(Event::SUCCESS, function ($behavior, $meta) use ($fs) {
            if ($meta->when == Action::SAVE) {
                $fs->close();
            }
        })
        ->when(Event::FAILURE, function ($b, $m) use ($fs) {
            $fs->close();
        })
        ->when(Event::ERROR, function ($b, $m) use ($fs) {
            $fs->close();
        });

        $fs->dirname = $queue;
        $fs->mkdir();

        $tail = self::tail($queue);
        do {
            $fs->basename = self::FILENAME . str_pad($tail, 11, '0', STR_PAD_LEFT);

            $tail++;
            if ($tail > 99999999999) {
                $tail = 0;
            }
            if (!$fs->exists()) {
                break;
            }
        } while ($fs->exists());

        $tail--;

        $fs->contents(serialize($item))->open();
    }

    /**
     * Retrieves the last item added to the specified queue.
     *
     * @param string $queue The name of the queue to retrieve the last item from.
     *
     * @return int The last item's index in the queue.
     */
    private static function tail($queue)
    {
        $stack = self::instance();

        $fs = new FileSystem(array('root' => $stack, 'mode' => 'r', 'filter' => 'file', 'doNotConfirm' => true, 'lock' => true));
        $fs->dirname = $queue;
        $array = $fs->listDir();

        if (!is_array($array) || count($array) < 1) {
            return 1;
        }
        // rsort($array);
        $last = end($array);

        $tail = str_replace([$stack, self::FILENAME, $queue,DIRECTORY_SEPARATOR], ['','','',''], $last);

        return (int)$tail;
    }

}
