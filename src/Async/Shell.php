<?php

namespace BlueFission\Async;

use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\System\Process;

/**
 * Class Shell for managing shell commands asynchronously.
 * This class extends the Async abstract class and provides specific implementations for executing shell commands.
 */
class Shell extends Async
{
    /**
     * Executes a shell command asynchronously.
     *
     * @param string $command The shell command to execute.
     * @param int $priority The priority of the task; higher values are processed earlier.
     * @return Shell The instance of the Shell class.
     */
    public static function do($command, $priority = 10)
    {
        $function = function () use ($command) {
            $process = new Process($command);
            yield from self::streamProcess($process);
        };

        return static::exec($function, $priority);
    }

    /** Stream a process until it exits, failing closed on unavailable status. */
    protected static function streamProcess(Process $process): \Generator
    {
        $failure = null;
        try {
            $process->start();
            while (($status = $process->status()) === true) {
                yield $process->output();
            }
            if ($status !== false) {
                throw new \RuntimeException('Process status unavailable.');
            }
            yield $process->output();
        } catch (\Throwable $exception) {
            $failure = $exception;
            throw $exception;
        } finally {
            try {
                $process->close();
            } catch (\Throwable $cleanupException) {
                if ($failure === null) {
                    throw $cleanupException;
                }
            }
        }
    }

    /**
     * Optional: Implement additional methods to handle process outputs, errors, or specific shell functionalities.
     */
}
