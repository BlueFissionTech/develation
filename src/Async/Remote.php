<?php

namespace BlueFission\Async;

use BlueFission\Connections\Curl;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;

/**
 * Class Remote to perform asynchronous HTTP requests using the Curl class.
 */
class Remote extends Async {

    /**
     * Executes a HTTP request using the Curl class.
     * 
     * @param string $url The URL to request.
     * @param array $options Options for the HTTP request including headers, body, etc.
     * @param int $priority The priority of the task in the queue.
     * @return Remote The instance of the Remote class.
     */
    public static function do($url, array $options = [], $priority = 10) {
        $function = function() use ($url, $options) {
            $curl = new Curl([
                'target' => $url,
                'method' => $options['method'] ?? 'get',
                'headers' => $options['headers'] ?? [],
                'username' => $options['username'] ?? null,
                'password' => $options['password'] ?? null,
            ]);

            if (!empty($options['data'])) {
                $curl->data($options['data']);
            }

            $curl->open();
            $curl->query();
            $result = $curl->result();
            $curl->close();

            if (!$result) {
                throw new \Exception("HTTP request failed: " . $curl->status());
            }

            return $result;
        };

        return static::exec($function, $priority);
    }

    /**
     * Override executeFunction to handle HTTP specific retries and errors.
     */
    protected function executeFunction($function) {
        try {
            $result = $function();
            yield $result;
            $this->perform(Event::SUCCESS);
        } catch (\Exception $e) {
            $this->perform(Event::FAILURE, ['message' => $e->getMessage()]);
            $this->logError($e);
            if ($this->shouldRetry($e)) {
                $this->retry($function);
            } else {
                yield null; // Yield null on non-retryable failure
            }
        }
    }

    /**
     * Determines whether the request should be retried based on the exception.
     *
     * @param \Exception $e The exception thrown during the request.
     * @return bool True if the request should be retried, false otherwise.
     */
    protected function shouldRetry(\Exception $e) {
        // Implement retry logic based on HTTP status codes or specific error messages
        return false;
    }

    protected function logError(\Exception $e) {
        // Optionally log the error to a specific log file or error tracking service
        parent::logError($e);
    }
}
