<?php

namespace BlueFission\Data\Queues;

interface IReliableQueue extends IQueue
{
    public static function claim($queue, int $leaseSeconds = 0, ?int $now = null): ?QueueReceipt;

    public static function acknowledge($queue, QueueReceipt $receipt): bool;

    public static function release($queue, QueueReceipt $receipt): bool;

    public static function recover($queue, ?int $now = null): int;
}
