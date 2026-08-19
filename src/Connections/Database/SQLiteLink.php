<?php

namespace BlueFission\Connections\Database;

use BlueFission\Val;
use BlueFission\Arr;
use BlueFission\Str;
use BlueFission\IObj;
use BlueFission\Net\HTTP;
use BlueFission\Connections\Connection;
use BlueFission\Behavioral\IConfigurable;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Meta;

/**
 * Class SQLiteLink
 *
 * This class extends the Connection class and implements the IConfigurable interface.
 * It is used for establishing a connection to an SQLite database and performing queries.
 */
class SQLiteLink extends Connection implements IConfigurable
{
    // Constants for different types of queries
    public const INSERT = 1;
    public const UPDATE = 2;
    public const UPDATE_SPECIFIED = 3;

    // protected property to store the database connection
    protected static $_database = [];
    private $_query;
    private $_last_row;
    protected $_database_key = null;
    private bool $_connection_acquired = false;

    // property to store the configuration
    protected $_config = [
        'database' => '',
        'table' => '',
        'key' => '_rowid',
        'ignore_null' => false,
    ];

    /**
     * Constructor method.
     *
     * This method sets the configuration, if provided, and sets the connection property to the last stored connection.
     *
     * @param mixed $config The configuration for the connection.
     * @return SQLiteLink
     */
    public function __construct($config = null)
    {
        parent::__construct($config);

        $this->syncConnection();

        return $this;
    }

    /**
     * Method to open a connection to an SQLite database.
     *
     * This method uses the configuration properties to establish a connection to an SQLite database.
     * If a connection is successfully established, it sets the connection property and the status property.
     *
     * @return void
     */
    protected function _open(): void
    {
        $database = $this->config('database');
        $databaseKey = self::databaseKey($database);

        if (
            $this->_connection_acquired
            && $this->_database_key === $databaseKey
            && self::isConnectionUsable($this->_connection)
        ) {
            $this->status(self::STATUS_CONNECTED);
            return;
        }

        if (!class_exists('SQLite3')) {
            throw new \Exception("SQLite3 not found");
        }

        if ($this->_connection_acquired) {
            $this->releaseConnection();
        }

        try {
            $db = self::acquireConnection($database);
            $this->_connection = $db;
            $this->_database_key = $databaseKey;
            $this->_connection_acquired = true;
            $status = self::STATUS_CONNECTED;

            $this->perform($this->_connection
                ? [Event::SUCCESS, Event::CONNECTED] : [Event::ACTION_FAILED, Event::FAILURE], new Meta(when: Action::CONNECT, info: $status));
        } catch (\Throwable $exception) {
            $this->_connection = null;
            $this->_connection_acquired = false;
            $status = $exception->getMessage() ?: self::STATUS_FAILED;
            $this->perform([Event::ACTION_FAILED, Event::FAILURE], new Meta(when: Action::CONNECT, info: $status));
        }

        $this->status($status);
    }

    /**
     * Close the database connection
     */
    protected function _close(): void
    {
        $this->releaseConnection();
        $this->_connection = null;
        $this->_database_key = null;
        $this->perform(State::DISCONNECTED);
    }

    /**
     * Get stats about the current query
     *
     * @return array  An array containing the current query
     */
    public function stats()
    {
        return ['query' => $this->_query];
    }

    /**
     * Perform a query on the database
     *
     * @param string|array $query  The query to perform
     * @return IObj
     */
    public function query($query = null): IObj
    {
        $this->perform(State::PERFORMING_ACTION, new Meta(when: Action::PROCESS));

        $db = $this->_connection;

        if ($db) {

            if (Val::isNotNull($query)) {
                $this->_query = $query;

                if (Arr::isAssoc($query)) {
                    $this->_data = $query;
                } elseif (Str::is($query)) {
                    try {
                        $this->_result = $db->query($query);
                        $status = $this->_result !== false
                            ? self::STATUS_SUCCESS
                            : ($db->lastErrorMsg() ?: self::STATUS_FAILED);
                        $this->status($status);
                    } catch (\Throwable $e) {
                        $this->_result = false;
                        $this->status($e->getMessage() ?: self::STATUS_FAILED);
                    }

                    return $this;
                }
            }
            $table = $this->config('table');

            $where = '';
            $update = false;

            $key = $this->config('key');

            if ($this->field($key)) {
                $value = self::sanitize($this->field($key));
                $keyField = self::sanitize($this->config('key'));
                $keyField = '`'.$this->config('key').'`';
                $where = $key ? "$keyField = $value" : '';
                $update = true;
            }
            $data = $this->_data;
            $type = ($update) ? ($this->config('ignore_null') ? self::UPDATE_SPECIFIED : self::UPDATE) : self::INSERT;
            $this->post($table, $data, $where, $type);
        } else {
            $status = self::STATUS_NOTCONNECTED;
            $this->perform([Event::ACTION_FAILED, Event::FAILURE], new Meta(when: Action::PROCESS, info: $status));
            $this->status($status);
        }

        return $this;
    }

    private function _read(): void
    {
        $table = $this->config('table');
        $data = $this->_data->val();
        $this->find($table, $data);
    }

    /**
     * Find a record in the database matching the given criteria
     *
     * @param string $table  The name of the table to search in
     * @param array $data  The criteria to match
     * @return void
     */
    private function find($table, $data): void
    {
        $db = $this->_connection;
        $success = false;

        if ($db) {
            $updates = [];
            $temp_values = [];
            $where = [1];
            $where_str = '';
            $query_str;

            foreach ($data as $a) {
                array_push($temp_values, self::sanitize($a));
            }

            $count = 0;
            foreach (Arr::keys($data) as $a) {
                array_push($where, $a ."=". $temp_values[$count]);
                $count++;
            }

            $where_str = implode(', ', $where);

            $query = "SELECT * FROM `".$table."` WHERE ".$where_str;

            $this->_query = $query;

            $this->perform([State::SENDING, State::RECEIVING, State::PROCESSING, State::BUSY]);
            $result = $db->query($query);
            $success = ($result) ? true : false;
            $status = ($success) ? self::STATUS_SUCCESS : ($db->lastErrorMsg() ?? self::STATUS_FAILED);
            $this->assign($result->fetchArray(SQLITE3_ASSOC));
            $this->halt([State::BUSY, State::SENDING, State::RECEIVING, State::PROCESSING]);

            $this->perform([Action::RECEIVE]);
            $this->perform(Event::RECEIVED, new Meta(data: $this->_result));

            $status = ($success) ? self::STATUS_SUCCESS : ($db->lastErrorMsg() ?? self::STATUS_FAILED);

            $this->perform(
                $this->_result ? [Event::SUCCESS, Event::COMPLETE, Event::PROCESSED] : [Event::ACTION_FAILED, Event::FAILURE],
                new Meta(when: Action::PROCESS, info: $status)
            );
        } else {
            $status = self::STATUS_NOTCONNECTED;
            $this->status($status);
        }

        $this->status($status);

        return;
    }

    /**
     * Inserts data into an SQLite database.
     *
     * @param string $table The name of the database table
     * @param array $data An associative array of fields and values to be inserted
     *
     * @return void
     */
    private function insert($table, $data): void
    {
        $this->perform(State::CREATING, new Meta(when: Action::PROCESS));

        $status = self::STATUS_NOTCONNECTED;

        $db = $this->_connection;
        $success = false;

        if ($db) {
            $insert = [];
            $field_string = '';
            $value_string = '';
            $temp_values = [];

            // Turn array into a string

            // Prepare each value for input
            foreach ($data as $a) {
                array_push($temp_values, self::sanitize($a));
            }

            $count = 0;
            foreach (Arr::keys($data) as $a) {
                if ($temp_values[$count] !== null && $temp_values[$count] !== 'NULL') {
                    $insert[$a] = $temp_values[$count];
                }

                $count++;
            }

            $field_string = implode('`, `', Arr::keys($insert));
            $value_string = implode(', ', $insert);

            $query = "INSERT INTO `".$table."`(`".$field_string."`) VALUES(".$value_string.")";

            $this->_query = $query;

            $this->perform([Action::SEND, State::SENDING], new Meta(when: Action::PROCESS, data: $insert));
            $this->perform([State::PROCESSING, State::BUSY]);
            try {
                $result = $db->exec($query);
                $success = ($result) ? true : false;
                $status = ($success) ? self::STATUS_SUCCESS : ($db->lastErrorMsg() ?? self::STATUS_FAILED);
                $lastRow = $db->lastInsertRowID();
                $this->_last_row = $lastRow;
                $this->_data['last_row'] = $lastRow;
            } catch (\Exception $e) {
                error_log($e->getMessage());
                $status = self::STATUS_FAILED;
                $this->perform(Event::ERROR, new Meta(when: Action::PROCESS, info: $e->getMessage()));
                $success = false;
                $this->status($e->getMessage());
            }
            $this->_result = $success;
            $this->halt([State::BUSY, State::SENDING, State::PROCESSING]);

            $this->perform(Event::SENT, new Meta(data: $data));
            $this->perform([Action::RECEIVE]);
            $this->perform(Event::RECEIVED, new Meta(data: $this->_result));
        } else {
            $status = self::STATUS_NOTCONNECTED;
            $this->perform([Event::ACTION_FAILED, Event::FAILURE], new Meta(when: Action::PROCESS, info: $status));
            $this->halt(State::CREATING);
            return;
        }

        $this->halt(State::CREATING);
        $status = ($success) ? self::STATUS_SUCCESS : self::STATUS_FAILED;
        $this->status($status);

        return;
    }

    /**
     * Updates data in an SQLite database.
     *
     * @param string $table The name of the database table
     * @param array $data An associative array of fields and values to be updated
     * @param string $where The condition for which to update the data
     * @param boolean $ignore_null Takes either a `1` or `0` (`true` or `false`) and determines if the entry
     *   will be replaced with a null value or kept the same when `NULL` is passed
     *
     * @return void
     */
    private function update($table, $data, $where, $ignore_null = false): void
    {
        $this->perform(State::UPDATING, new Meta(when: Action::PROCESS));

        $db = $this->_connection;
        $success = false;
        $status = self::STATUS_NOTCONNECTED;

        if ($db) {
            $updates = [];
            $temp_values = [];
            $update_string = '';
            $query_str;

            foreach ($data as $a) {
                array_push($temp_values, self::sanitize($a));
            }

            $count = 0;
            foreach (Arr::keys($data) as $a) {
                if ($ignore_null === true) {
                    if ($temp_values[$count] !== null && $temp_values[$count] !== 'NULL') {
                        array_push($updates, "`{$a}`" ."=". $temp_values[$count]);
                    }
                } else {
                    array_push($updates, "`{$a}`" ."=". $temp_values[$count]);
                }
                $count++;
            }

            $update_string = implode(', ', $updates);

            $query = "UPDATE `".$table."` SET ".$update_string." WHERE ".$where;

            $this->_query = $query;

            $this->perform([Action::SEND, State::SENDING], new Meta(when: Action::PROCESS, data: $updates));
            $this->perform([State::PROCESSING, State::BUSY]);

            try {
                $result = $db->exec($query);
                $success = ($result) ? true : false;
                $status = ($success) ? self::STATUS_SUCCESS : ($db->lastErrorMsg() ?? self::STATUS_FAILED);
                $this->_result = $result;
            } catch (\Exception $e) {
                error_log($e->getMessage());
                $status = self::STATUS_FAILED;
                $this->perform(Event::ERROR, new Meta(when: Action::PROCESS, info: $e->getMessage()));
                $success = false;
                $this->status($e->getMessage());
            }

            $this->_result = $success;

            $this->halt([State::BUSY, State::SENDING, State::PROCESSING]);

            $this->perform(Event::SENT, new Meta(data: $data));
            $this->perform([Action::RECEIVE]);
            $this->perform(Event::RECEIVED, new Meta(data: $this->_result));
        } else {
            $status = self::STATUS_NOTCONNECTED;
            $this->perform([Event::ACTION_FAILED, Event::FAILURE], new Meta(when: Action::PROCESS, info: $status));
            $this->halt(State::CREATING);
            return;
        }

        $this->halt(State::CREATING);
        $status = ($success) ? self::STATUS_SUCCESS : self::STATUS_FAILED;
        $this->status($status);

        return;
    }

    //Posts data into the database using specified method
    /**
     * Posts data into the database using specified method
     *
     * @param string $table The name of the database table
     * @param array $data An associative array of fields and values to be affected
     * @param string $where A MySQL WHERE clause (optional)
     * @param int $type Determines the type of query used. 1 for INSERT, 2 for UPDATE, 3 for UPDATE ignoring nulls (optional)
     *
     * @return void
     */
    private function post($table, $data, $where = null, $type = null): void
    {
        $db = $this->_connection;
        $status = '';
        $success = false;
        $ignore_null = false;
        $last_row = null;

        if ($where == '' && ($type == self::INSERT || $type == self::UPDATE)) {
            $where = "1";
        } elseif (isset($where) && $where != '' && $type != self::UPDATE_SPECIFIED) {
            $type = self::UPDATE;
        }
        if (isset($table) && $table != '') {
            if (count($data) >= 1) {
                switch ($type) {
                    case self::INSERT:
                        if ($this->insert($table, $data)) {
                            $status = "Successfully Inserted Entry.";
                            $last_row = $db->lastInsertRowID();
                            $success = true;
                        } else {
                            $status = "Insert Failed. Reason: " . $db->lastErrorMsg();
                        }
                        break;
                    case self::UPDATE_SPECIFIED:
                        $ignore_null = true;
                        // no break
                    case self::UPDATE:
                        if (isset($where) && $where != '') {
                            if ($this->update($table, $data, $where, $ignore_null)) {
                                $status = "Successfully Updated Entry.";
                                $last_row = $db->lastInsertRowID();
                                $success = true;
                            } else {
                                $status = "Update Failed. Reason: " . $db->lastErrorMsg();
                            }
                        } else {
                            $status = "No Target Entry Specified.";
                        }
                        break;
                    default:
                        $status = "Query Type Not Supported.";
                        break;
                }
            } else {
                $status = "Fields and Values do not match or Insufficient Fields.";
            }
        } else {
            $status = "No Target Table Specified";
        }

        $this->perform(
            $this->_result ? [Event::SUCCESS, Event::COMPLETE, Event::PROCESSED] : [Event::ACTION_FAILED, Event::FAILURE],
            new Meta(when: Action::PROCESS, info: $status)
        );

        $this->_last_row = $last_row ? $last_row : $this->_last_row;

        return;
    }

    /**
     * Get or set the database name
     *
     * @param string|null $database The database name to set
     * @return string|null The database name
     */
    public function database($database = null)
    {
        if (Val::isNull($database)) {
            return $this->config('database');
        }

        $databaseKey = self::databaseKey($database);

        if ($this->_database_key !== $databaseKey) {
            $this->releaseConnection();
            $this->_connection = null;
            $this->_database_key = $databaseKey;
        }

        $this->config('database', $database);

        return $this;
    }

    /**
     * Get the last row inserted
     *
     * @return integer The last row id
     */
    public function lastRow()
    {
        return $this->_last_row;
    }

    /**
     * Sanitize the given string
     *
     * @param string $string The string to sanitize
     * @param boolean $datetime Indicates if the string is a datetime value
     * @return string The sanitized string
     */
    public static function sanitize($string, $datetime = false, $database = null)
    {
        $db = self::connectionFor($database);

        if ($string instanceof \BlueFission\IVal) {
            $string = $string->val();
        }

        if (Val::isNull($string)) {
            return 'NULL';
        }

        if (is_bool($string)) {
            return $string ? '1' : '0';
        }

        if (is_int($string) || is_float($string)) {
            return (string)$string;
        }

        $value = (string)$string;

        if ($datetime === true && preg_match('/^(\d+)\/(\d+)\/(\d{4})$/', $value, $matches)) {
            $value = "{$matches[3]}-{$matches[1]}-{$matches[2]} 12:00:00";
        } elseif (preg_match('/^(\d+)\/(\d+)\/(\d{4})$/', $value, $matches)) {
            $value = "{$matches[3]}-{$matches[1]}-{$matches[2]}";
        }

        if ($value === 'NOW()' || $value === 'NULL') {
            return $value;
        }

        if ($db) {
            $value = $db->escapeString(stripslashes($value));
        } elseif (class_exists('SQLite3')) {
            $value = \SQLite3::escapeString(stripslashes($value));
        }

        return "'" . $value . "'";
    }

    /**
     * Determines if the given table exists in the database
     *
     * @param string $table Name of the table to check for
     *
     * @return bool true if the table exists, false otherwise
     */
    public static function tableExists($table, $database = null)
    {
        $db = self::connectionFor($database);
        $temporary = false;

        if (!$db && Val::isNotEmpty($database) && class_exists('SQLite3')) {
            $db = new \SQLite3($database);
            $db->enableExceptions(true);
            $temporary = true;
        }

        if (!$db) {
            return false;
        }

        $table = self::sanitize($table, false, $database);
        $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name={$table}");

        $exists = $result && $result->fetchArray();

        if ($temporary) {
            $db->close();
        }

        return (bool)$exists;
    }

    protected function syncConnection(): void
    {
        $database = $this->config('database');
        $this->_database_key = self::databaseKey($database);
        $this->_connection = null;
        $this->_connection_acquired = false;
    }

    protected static function connectionFor($database = null)
    {
        if (Val::isNull(self::$_database)) {
            self::$_database = [];
        }

        if (Val::isEmpty($database)) {
            $entry = Arr::size(self::$_database) > 0 ? end(self::$_database) : null;
            return self::entryConnection($entry);
        }

        $databaseKey = self::databaseKey($database);

        if (!Arr::hasKey(self::$_database, $databaseKey)) {
            return null;
        }

        $connection = self::entryConnection(self::$_database[$databaseKey]);

        return self::isConnectionUsable($connection) ? $connection : null;
    }

    protected static function acquireConnection($database): \SQLite3
    {
        $databaseKey = self::databaseKey($database);
        $connection = self::connectionFor($database);

        if ($connection) {
            self::$_database[$databaseKey]['references']++;
            return $connection;
        }

        unset(self::$_database[$databaseKey]);
        $connection = new \SQLite3($database);
        $connection->enableExceptions(true);
        self::$_database[$databaseKey] = [
            'connection' => $connection,
            'references' => 1,
        ];

        return $connection;
    }

    protected function releaseConnection(): void
    {
        if (!$this->_connection_acquired || Val::isNull($this->_database_key)) {
            return;
        }

        $databaseKey = $this->_database_key;
        $entry = self::$_database[$databaseKey] ?? null;
        $connection = self::entryConnection($entry);

        if ($connection === $this->_connection && Arr::is($entry)) {
            self::$_database[$databaseKey]['references']--;

            if (self::$_database[$databaseKey]['references'] <= 0) {
                if (self::isConnectionUsable($connection)) {
                    $connection->close();
                }
                unset(self::$_database[$databaseKey]);
            }
        }

        $this->_connection_acquired = false;
    }

    protected static function entryConnection($entry): ?\SQLite3
    {
        if ($entry instanceof \SQLite3) {
            return $entry;
        }

        if (Arr::is($entry) && ($entry['connection'] ?? null) instanceof \SQLite3) {
            return $entry['connection'];
        }

        return null;
    }

    protected static function isConnectionUsable($connection): bool
    {
        if (!$connection instanceof \SQLite3) {
            return false;
        }

        try {
            $connection->lastErrorCode();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    protected static function databaseKey($database): string
    {
        if (Val::isNull($database)) {
            return '';
        }

        $database = (string)$database;

        if ($database === ':memory:') {
            return $database;
        }

        $directory = dirname($database);
        $resolvedDirectory = realpath($directory);

        if ($resolvedDirectory !== false) {
            $database = $resolvedDirectory . DIRECTORY_SEPARATOR . basename($database);
        }

        return $database;
    }
}
