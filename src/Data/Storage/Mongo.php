<?php

namespace BlueFission\Data\Storage;

use BlueFission\IObj;
use BlueFission\Connections\Database\MongoLink;

/**
 * Class Mongo
 *
 * A Mongo storage class for accessing MongoDB databases.
 */
class Mongo extends Storage
{
    /**
     * @var string $_collection The name of the collection in the MongoDB database.
     */
    private $_collection;

    /**
     * @var array $_config The configuration options for the Mongo storage class.
     */
    protected $_config = [
        'location' => '',
        'name' => '',
    ];

    /**
     * Mongo constructor.
     *
     * @param null $config The configuration options for the Mongo storage class.
     */
    public function __construct($config = null)
    {
        parent::__construct($config);
    }

    /**
     * Activates the Mongo storage class and sets up the connection to the MongoDB database.
     */
    public function activate(): IObj
    {
        $this->_source = new MongoLink();
        if ($this->config('location')) {
            $this->_source->database($this->config('location'));
        }

        if ($this->config('name')) {
            $this->_collection = $this->config('name');
        }

        if (!$this->_source) {
            $this->status(self::STATUS_FAILED_INIT);
        }

        return $this;
    }

    /**
     * Reads data from the MongoDB database.
     */
    public function read(): IObj
    {
        $db = $this->_source;

        if ($db) {
            $collection = $this->config('name');

            $db->config('collection', $collection);

            $success = $db->find($collection, $this->_data);

            $this->_result = $success;

            $this->assign($success->toArray()[0]);

            $status = $success ? self::STATUS_SUCCESS : ($db->status() ? $db->status : self::STATUS_FAILED);
            $this->status($status);
        }

        return $this;
    }

    /**
     * Deletes data from the MongoDB database.
     */
    public function delete(): IObj
    {
        $db = $this->_source;

        if ($db) {
            $collection = $this->config('name');

            $db->config('collection', $collection);

            $success = $db->delete($collection, $this->_data);

            $this->_result = $success;

            $status = $success ? self::STATUS_SUCCESS : ($db->status() ? $db->status : self::STATUS_FAILED);
            $this->status($status);
        }

        return $this;
    }

    /**
     * Writes data to the MongoDB database.
     */
    public function write(): IObj
    {

        $db = $this->_source;

        if ($db) {
            $collection = $this->config('name');

            $db->config('collection', $collection);
            // $db->config('ignore_null', $this->config('ignore_null'));
            $success = $db->query($this->_data);

            if ($success) {
                $affected_row = $db->last_row();
                $this->_last_row_affected = $affected_row;
            }

            $status = $success ? self::STATUS_SUCCESS : ($db->status() ? $db->status : self::STATUS_FAILED);
            $this->status($status);
        }

        return $this;
    }

    /**
     * Performs map-reduce operation on Mongo database.
     *
     * @param string $map The map function.
     * @param string $reduce The reduce function.
     * @param string $output The output function.
     * @param string $action The action to be performed on map-reduce operation.
     *
     * @return mixed Result of the map-reduce operation.
     */
    public function mapReduce($map, $reduce, $output, $action = null)
    {
        $db = $this->_source;

        return $db->mapReduce($map, $reduce, $output, $action);
    }

    /**
     * Returns contents of the database.
     *
     * @param mixed $data Optional data to be assigned.
     *
     * @return mixed Data stored in the database.
     */
    public function contents($data = null)
    {
        $data = parent::contents($data);
        if ($data) { // if something was assigned
            $data = ($this->_result) ? $this->_result : $this->data();

            return $data;
        }
    }

}
