<?php

namespace BlueFission\Async;

use BlueFission\Connections\Curl;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Meta;
use Exception;

/**
 * Class Remote to perform asynchronous HTTP requests using the Curl class.
 */
class Remote extends Async
{
    /**
     * Executes an HTTP request asynchronously.
     *
     * @param string $url The target URL.
     * @param array $options HTTP options (method, headers, auth, data, etc).
     * @param int $priority Task priority for async queue.
     * @return Promise
     * @throws Exception if the request setup fails.
     */
    public static function do(string $url, array $options = [], int $priority = 10): Promise
    {
        $function = function ($resolve, $reject) use ($url, $options) {
            $result = null;

            $curl = new Curl([
                'target' => $url,
                'method' => $options['method'] ?? 'get',
                'headers' => $options['headers'] ?? [],
                'username' => $options['username'] ?? null,
                'password' => $options['password'] ?? null,
            ]);

            if (!empty($options['data'])) {
                $curl->assign($options['data']);
            }

            $curl
                ->when(Event::CONNECTED, function ($behavior, $args) use ($curl) {
                    $curl->query();
                })
                ->when(Event::PROCESSED, function ($behavior, $args) use (&$result, $resolve, $curl) {
                    $result = $curl->result();
                    $curl->close();
                    $resolve($result);
                })
                ->when(Event::FAILURE, (function ($behavior, $args) use ($reject) {
                    $reject($args->info);
                    $code = $this->_connection
                        ? curl_getinfo($this->_connection, CURLINFO_HTTP_CODE)
                        : 'No Connection';
                    throw new Exception("HTTP request failed: ({$code}) " . $args->info);
                })->bindTo($curl, $curl))
                ->when(Event::ERROR, (function ($behavior, $args) use ($reject) {
                    $reject($args->info);
                    $code = curl_getinfo($this->_connection, CURLINFO_HTTP_CODE);
                    throw new Exception("HTTP request error: ({$code}) " . $args->info);
                })->bindTo($curl, $curl))
                ->open();

            if (!$result) {
                throw new Exception("HTTP response empty: " . $curl->status());
            }
        };

        return static::exec($function, $priority);
    }

    /**
     * Executes a given function and handles retry and logging.
     *
     * @param callable $function
     * @return \Generator
     */
    protected function executeFunction($function): \Generator
    {
        try {
            $result = $function();
            yield $result;
            $this->perform(Event::SUCCESS);
        } catch (\Exception $e) {
            $this->perform(Event::FAILURE, new Meta(['message' => $e->getMessage()]));
            $this->logError($e);

            if ($this->shouldRetry($e)) {
                $this->retry($function);
            } else {
                yield null;
            }
        }
    }

    /**
     * Determines whether the request should be retried.
     *
     * @param \Exception $e
     * @return bool
     */
    protected function shouldRetry(\Exception $e): bool
    {
        $message = $e->getMessage();
        return str_contains($message, 'timed out') || str_contains($message, '(500)');
    }

    /**
     * Logs an error using the parent's logger.
     *
     * @param \Exception $e
     * @return void
     */
    protected function logError(\Exception $e): void
    {
        parent::logError($e);
    }
}
