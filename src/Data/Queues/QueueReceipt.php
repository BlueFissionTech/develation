<?php

namespace BlueFission\Data\Queues;

final class QueueReceipt
{
    public function __construct(
        public readonly string $id,
        public readonly mixed $payload,
        public readonly int $attempts,
        public readonly int $leaseExpiresAt,
        public readonly string $token,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'payload' => $this->payload,
            'attempts' => $this->attempts,
            'lease_expires_at' => $this->leaseExpiresAt,
            'token' => $this->token,
        ];
    }
}
