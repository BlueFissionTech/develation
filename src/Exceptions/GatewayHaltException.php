<?php

namespace BlueFission\Exceptions;

use BlueFission\Services\GatewayOutcome;

/**
 * Allows a gateway to halt application execution through an exception path.
 */
class GatewayHaltException extends \RuntimeException
{
    private GatewayOutcome $outcome;

    public function __construct(mixed $response = null, string $message = 'Gateway halted application execution.')
    {
        $this->outcome = GatewayOutcome::halt($response);

        parent::__construct($message);
    }

    public function outcome(): GatewayOutcome
    {
        return $this->outcome;
    }
}
