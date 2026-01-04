<?php

namespace BlueFission\Utils;

use BlueFission\Data\Storage\Storage;
use BlueFission\Behavioral\Behavioral;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\DevElation as Dev;

class Mem
{
    protected static $pool = [];
    protected static $audit = [];
    protected static $threshold = 300; // Time in seconds to keep unused objects
    protected static $storage;
    protected static $id_key = 'memory_pool_id';
    public const SLEEPING = 'SLEEPING'; // Placeholder for sleeping objects

    public static function setStorage(Storage $storage)
    {
        self::$storage = $storage;
    }

    public static function register($object, $id = null)
    {
        $id = $id ?: spl_object_hash($object);
        self::$pool[$id] = $object;
        self::$audit[$id] = ['time' => microtime(true), 'used' => false];
    }

    private static function store($id, $object)
    {
        if (self::$storage) {
            $serializedData = serialize($object);

            self::$storage->clear();
            self::$storage->{self::$id_key} = $id;
            self::$storage->data = $serializedData;
            self::$storage->write();
        }
    }

    private static function retrieve($id)
    {
        if (self::$storage) {
            self::$storage->{self::$id_key} = $id;
            $serializedData = self::$storage->read()->data;
            self::$storage->delete();
            return unserialize($serializedData);
        }
        return null;
    }

    public static function unregister($id)
    {
        if (isset(self::$pool[$id])) {
            unset(self::$pool[$id]);
            unset(self::$audit[$id]);
        }
    }

    public static function threshold($seconds)
    {
        self::$threshold = $seconds;
    }

    public static function get($id)
    {
        if (isset(self::$pool[$id]) && self::$pool[$id] !== self::SLEEPING) {
            self::$audit[$id]['used'] = true;
            return self::$pool[$id];
        }
        return null;
    }

    public static function flush()
    {
        $currentTime = microtime(true);

        foreach (self::$audit as $id => $info) {
            if (!$info['used'] && ($currentTime - $info['time'] > self::$threshold)) {
                self::unregister($id);
            }
        }

        gc_collect_cycles();
    }

    public static function assess()
    {
        // iterate through objects, if they implmenet Behavioral and are IDLE, then mark them as unused
        foreach (self::$pool as $id => $object) {
            if ($object instanceof Behavioral && $object->is(State::IDLE)) {
                self::$audit[$id]['used'] = false;
            }
        }
    }

    public static function audit()
    {
        $unused = [];
        foreach (self::$audit as $id => $info) {
            if (!$info['used']) {
                $unused[$id] = $info;
            }
        }
        self::assess();
        return $unused;
    }

    public static function wakeup($id)
    {
        if (isset(self::$pool[$id]) && self::$pool[$id] === self::SLEEPING) {
            // Assume stored data is serialized
            self::$pool[$id] = self::retrieve($id);
            Dev::do(null, [self::$pool[$id]]);
            self::$audit[$id]['used'] = true;
        }
    }

    public static function sleep($id)
    {
        if (isset(self::$pool[$id]) && self::$pool[$id] !== self::SLEEPING) {
            Dev::do(null, [self::$pool[$id]]);

            if (self::$storage) {
                $serializedData = serialize(self::$pool[$id]);
                self::store($id, $serializedData);
            }
            self::$pool[$id] = self::SLEEPING; // Mark the object as stored, not active in memory
            self::$audit[$id]['used'] = false;
        }
    }

    public static function sleepAll()
    {
        foreach (self::$pool as $id => $object) {
            if ($object !== self::SLEEPING) {
                self::sleep($id);
            }
        }
    }
}
