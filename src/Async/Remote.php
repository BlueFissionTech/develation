<?php

namespace BlueFission\Async;

use BlueFission\Connections\Curl;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Meta;

/**
 * Class Remote to perform asynchronous HTTP requests using the Curl class.
 */
class Remote extends Async {

    /**
     * Executes a HTTP request using the Curl class.
     * 
     * @param string $_url The URL to request.
     * @param array $_options Options for the HTTP request including headers, body, etc.
     * @param int $_priority The priority of the task in the queue.
     * @return Remote The instance of the Remote class.
     */
    public static function do($_url, array $_options = [], $_priority = 10) {
        $_function = function($_resolve, $_reject) use ($_url, $_options) {
            $_result = null;

            $_curl = new Curl([
                'target' => $_url,
                'method' => $_options['method'] ?? 'get',
                'headers' => $_options['headers'] ?? [],
                'username' => $_options['username'] ?? null,
                'password' => $_options['password'] ?? null,
            ]);

            if (!empty($_options['data'])) {
                $_curl->assign($_options['data']);
            }

            $_curl
            ->when(Event::CONNECTED, function($_behavior, $_args) use ($_curl) {
                $_curl->query();
            })
            ->when(Event::PROCESSED, function($_behavior, $_args) use ($_resolve, $_curl, &$_result) {
                $_result = $_curl->result();
                $_curl->close();
                $_resolve($_result);
            })
            ->when(Event::FAILURE, (function($_behavior, $_args) use ($_reject) {
                $_reject($_args->info);
                $_httpStatusCode = ($this->_connection ? curl_getinfo($this->_connection, CURLINFO_HTTP_CODE) : 'No Connection');

                throw new \Exception("HTTP request failed: ({$_httpStatusCode}) " . $_args->info);
            })->bindTo($_curl, $_curl))
            
            ->when(Event::ERROR, (function($_behavior, $_args) use ($_reject) {
                $_reject($_args->info);
                $_httpStatusCode = curl_getinfo($this->_connection, CURLINFO_HTTP_CODE);

                throw new \Exception("HTTP request error: ({$_httpStatusCode}) " . $_args->info);
            })->bindTo($_curl, $_curl))
            ->open();

            if (!$_result) {
                throw new \Exception("HTTP response empty: " . $_curl->status());
            }
        };

        return static::exec($_function, $_priority);
    }

    /**
     * Override executeFunction to handle HTTP specific retries and errors.
     */
    protected function executeFunction($_function) {
        try {
            $_result = $_function();
            yield $_result;
            $this->perform(Event::SUCCESS);
        } catch (\Exception $_e) {
            $this->perform(Event::FAILURE, ['message' => $_e->getMessage()]);
            $this->logError($_e);
            if ($this->shouldRetry($_e)) {
                $this->retry($_function);
            } else {
                yield null; // Yield null on non-retryable failure
            }
        }
    }

    /**
     * Determines whether the request should be retried based on the exception.
     *
     * @param \Exception $_e The exception thrown during the request.
     * @return bool True if the request should be retried, false otherwise.
     */
    protected function shouldRetry(\Exception $_e) {
        // Implement retry logic based on HTTP status codes or specific error messages
        $_retry = false;
        
        if (strpos($_e->getMessage(), 'timed out') !== false) {
            $_retry = true;
        }

        if (strpos($_e->getMessage(), '(500)') !== false) {
            $_retry = true;
        }

        return $_retry;
    }

    protected function logError(\Exception $_e) {
        // Optionally log the error to a specific log file or error tracking service
        parent::logError($_e);
    }
}
