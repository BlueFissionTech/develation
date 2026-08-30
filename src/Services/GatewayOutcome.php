<?php

namespace BlueFission\Services;

/**
 * Transport-neutral outcome returned by an application gateway.
 */
final class GatewayOutcome
{
    private function __construct(
        private bool $halted,
        private mixed $response = null
    ) {
    }

    public static function proceed(): self
    {
        return new self(false);
    }

    public static function halt(mixed $response = null): self
    {
        return new self(true, $response);
    }

    public function halted(): bool
    {
        return $this->halted;
    }

    public function response(): mixed
    {
        return $this->response;
    }
}
