<?php
namespace BlueFission\Data\Queues;

use BlueFission\Data\Storage\Mysql;

class MysqlQueue extends Queue implements IQueue {
    /**
     * @var Mysql $storage
     */
    private $storage;
    
    /**
     * MysqlQueue constructor.
     *
     * @param Mysql $storage
     */
    public function __construct(Mysql $storage) {
        $this->storage = $storage;
    }
    
    /**
     * Enqueue an item to the queue.
     *
     * @param string $queue
     * @param mixed $item
     *
     * @return int|false
     */
    public function enqueue(string $queue, $item) {
        $id = $this->storage->increment($queue . "_tail");
        if ($id === FALSE) {
            if ($this->storage->add($queue . "_tail", 1) === FALSE) {
                $id = $this->storage->increment($queue . "_tail");
                if ($id === FALSE) {
                    return FALSE;
                }
            } else {
                $id = 1;
                $this->storage->add($queue . "_head", $id);
            }
        }
        
        if ($this->storage->add($queue . "_" . $id, $item) === FALSE) {
            return FALSE;
        }
        
        return $id;
    }
    
    /**
     * Dequeue an item from the queue.
     *
     * @param string $queue
     *
     * @return mixed|false
     */
    public function dequeue(string $queue) {
        $head = $this->storage->get($queue . "_head");
        if ($head === FALSE) {
            return FALSE;
        }
        
        $item = $this->storage->get($queue . "_" . $head);
        if ($item === FALSE) {
            return FALSE;
        }
        
        if ($this->storage->delete($queue . "_" . $head) === FALSE) {
            return FALSE;
        }
        
        if ($this->storage->increment($queue . "_head") === FALSE) {
            return FALSE;
        }
        
        return $item;
    }
}
