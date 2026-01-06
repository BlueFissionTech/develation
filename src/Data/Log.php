<?php

namespace BlueFission\Data;

use BlueFission\Val;
use BlueFission\IObj;
use BlueFission\Data\FileSystem;
use BlueFission\Net\Email;

/**
 * Class Log
 *
 * This class is for managing log data, it provides methods for reading, writing,
 * and pushing log data to various locations such as file, email, or system logs.
 *
 * The class implements the iData interface to provide a common way of accessing data.
 */
class Log extends Data implements iData
{
    /**
     * @var array $_config - Configuration options for the Log class.
     *      storage - The destination where the log data should be stored.
     *      file - The file path where log data should be stored.
     *      email - The email address where log data should be sent.
     *      subject - The subject line for the email.
     *      from - The sender email address for the email.
     *      max_logs - The maximum number of logs that should be stored.
     *      instant - Whether to write the log data immediately or store it until write() is called.
     */
    protected $_config = array('storage' => '', 'file' => 'application.log', 'email' => '', 'subject' => '', 'from' => '', 'max_logs' => 100, 'instant' => false);

    /**
     * @var array $_messages - An array to store log data.
     */
    public static $_messages;

    /**
     * @var int SYSTEM - Constant value to indicate that log data should be stored in the system logs.
     */
    public const SYSTEM = 0;

    /**
     * @var int EMAIL - Constant value to indicate that log data should be sent as an email.
     */
    public const EMAIL = 1;

    /**
     * @var int FILE - Constant value to indicate that log data should be stored in a file.
     */
    public const FILE = 3;

    /**
     * @var Log $_instance - An instance of the Log class.
     */
    public static $_instance;

    /**
     * Log constructor.
     *
     * @param array|null $config - An array of configuration options.
     */
    public function __construct($config = null)
    {
        parent::__construct();
        if (is_array($config)) {
            $this->config($config);
        }

        if (!$this->config('storage')) {
            $this->config('storage', self::FILE);
        }
        self::$_messages = array();
    }

    /**
     * Creates a singleton instance of the Log class.
     *
     * @return Log - An instance of the Log class.
     */
    public static function instance()
    {
        if (Val::isNull(Log::$_instance)) {
            Log::$_instance = new Log();
        }

        return Log::$_instance;
    }

    /**
     * Adds a message to the log data.
     *
     * @param string $message - The log message to be added.
     */
    public function push($message): IObj
    {
        $time = date('Y-m-d G:i:s');
        $this->field($time, $message);
        if ($this->config('instant')) {
            $this->write();
            $this->clear();
        }

        return $this;
    }

    /**
     * The `read` method reads the log data based on the storage type specified in the configuration.
     * If the storage type is `FILE`, it uses the `FileSystem` class to read the log file.
     * If the storage type is not specified, it defaults to `SYSTEM`.
     *
     * @return IObj
     */
    public function read(): IObj
    {
        $destination = $this->config('file');
        $type = $destination ? $this->config('storage') : self::SYSTEM;
        if ($type == self::FILE && $destination) {
            $file_config = ['mode' => 'a'];
            $messenger = new FileSystem($file_config);
            $messenger->open($destination);
            $messenger->read();
            $status = $messenger->status();
            $data = $messenger->data();

            return $this;
        }
        $this->status("Cannot open log files with current settings.");

        return $this;
    }

    /**
     * The `write` method writes the log data to the specified storage type.
     * If the storage type is `FILE`, it uses the `FileSystem` class to write the log data to a file.
     * If the storage type is `EMAIL`, it uses the `Email` class to send an email with the log data.
     * If the storage type is not specified, it defaults to `SYSTEM`.
     *
     * @param string $file The file name to be used for writing log data.
     * @return IObj
     */
    public function write($file = null): IObj
    {
        $message = $this->message();
        $status = null;

        if ($message != '') {
            $destination = $this->config('email') ? $this->config('email') : $this->config('file');
            $type = $destination ? $this->config('storage') : self::SYSTEM;

            switch ($type) {
                case self::FILE:
                    if (class_exists('FileSystem')) {
                        $file_config = ['mode' => 'a'];
                        $messenger = new FileSystem($file_config);
                        $messenger->file($this->config('file'));
                        $messenger->contents($message);
                        $messenger->write();
                        $status = $messenger->status();
                    } else {
                        $status = error_log($message, $type, $destination) ? "Errors saved by system" : "Unable to save errors. Ironic.";
                    }
                    break;
                case self::EMAIL:
                    if (class_exists('Email')) {
                        $messenger = new Email($destination, $from, $subject, $message);
                        $messenger->send();
                        $status = $messenger->status();
                    } else {
                        $status = mail($destination, $from, $subject, $message) ? "Log emailed to recipient" : (error_log($message, $type, $destination) ? "Errors emailed by system" : "Unable to send email report");
                    }
                    break;
                default:
                case self::SYSTEM:
                    $status = error_log($message, $type, $destination) ? "Errors reported to system" : "Unable to record error";
                    break;
            }
        }

        $this->status($status);

        return $this;
    }

    /**
     * Deletes the log file
     *
     * @return IObj
     */
    public function delete(): IObj
    {
        $destination = $this->config('file');
        $type = $destination ? $this->config('storage') : self::SYSTEM;
        if ($type == self::FILE && class_exists('FileSystem')) {
            $file_config = ['mode' => 'a'];
            $messenger = new FileSystem($file_config);
            $messenger->file($destination);
            $messenger->delete();
            $status = $messenger->status();

            return $this;
        }
        $this->status("Cannot delete log files with current settings.");

        return $this;
    }

    /**
     * Gets the log message from the given records
     *
     * @param null $records
     * @return string
     */
    private function message($records = null): string
    {
        $records = (Val::isNull($records)) ? $this->config('max_logs') : $records;
        $raw = $this->data();
        $message = array_slice($raw, -($records));
        $message = array_filter($message);
        // $output = implode("\n", $message)."\n";
        $output = '';
        foreach ($message as $time => $line) {
            $output .= "{$time} - {$line}\n";
        }
        return $output;
    }

    /**
     * Sends an alert message to the specified email address
     *
     * @return IObj
     */
    public function alert(): IObj
    {
        $destination = $this->config('email');
        $type = self::EMAIL;
        $message = $this->message;

        if (class_exists('Email')) {
            $messenger = new Email($destination, $from, $subject, $message);
            $messenger->send();
            $status = $messenger->status();
        } else {
            $status = mail($destination, $from, $subject, $message) ? "Alert emailed to recipient" : (error_log($message, $type, $destination) ? "Alert emailed by system" : "Unable to send alert");
        }

        $this->status($status);

        return $this;
    }

    /**
     * Writes the log to file before the instance is destroyed
     */
    public function __destruct()
    {
        if (!$this->config('instant')) {
            $this->write();
        }
    }
}
