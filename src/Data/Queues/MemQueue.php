<?php

namespace BlueFission\Data\Queues;

use BlueFission\Arr;
use BlueFission\Collections\Collection;
use BlueFission\Security\Hash;
use BlueFission\Str;
use InvalidArgumentException;
use Memcached;
use RuntimeException;

/**
 * Memcached-backed queue with CAS-protected reliable delivery.
 */
class MemQueue extends Queue implements IReliableQueue
{
    public const MEMQ_TTL = 0;

    private const RESULT_NOT_FOUND = 16;

    private static ?object $_stack = null;

    private static string $_memq_pool = 'localhost:11211';

    private static array $_config = [
        'queue_prefix' => 'develation:memqueue:',
        'lease_seconds' => 60,
        'max_attempts' => 3,
        'cas_retries' => 20,
    ];

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function configure(array $config): void
    {
        self::$_config = (new Arr(self::$_config))->merge($config)->val();
    }

    public static function setClient(object $client): void
    {
        self::$_stack = $client;
    }

    public static function setPool(string $pool): void
    {
        self::$_memq_pool = $pool;
        self::$_stack = null;
    }

    public static function reset(): void
    {
        self::$_stack = null;
        self::$_memq_pool = 'localhost:11211';
        self::$_config = [
            'queue_prefix' => 'develation:memqueue:',
            'lease_seconds' => 60,
            'max_attempts' => 3,
            'cas_retries' => 20,
        ];
        self::$_mode = self::FIFO;
    }

    public static function isEmpty($queue)
    {
        self::assertQueue($queue);
        [$state] = self::readState($queue);

        return Arr::isEmpty($state['ready']);
    }

    public static function enqueue($queue, $item)
    {
        self::assertQueue($queue);
        $id = bin2hex(random_bytes(16));

        if (!self::instance()->set(self::payloadKey($queue, $id), serialize($item), self::MEMQ_TTL)) {
            return false;
        }

        try {
            self::mutateState($queue, function (array $state) use ($id): array {
                $state['ready'][] = $id;
                $state['attempts'][$id] = 0;
                $state['payloads'][$id] = true;

                return [$state, $id];
            });
        } catch (RuntimeException $exception) {
            self::instance()->delete(self::payloadKey($queue, $id));
            throw $exception;
        }

        return $id;
    }

    public static function dequeue($queue, $after = false, $until = false)
    {
        self::assertQueue($queue);

        if ($after === false && $until === false) {
            $receipt = self::claim($queue);
            if ($receipt === null) {
                return false;
            }

            if (!self::acknowledge($queue, $receipt)) {
                throw new RuntimeException('Memcached queue acknowledgement failed.');
            }

            return $receipt->payload;
        }

        $offset = max(0, (int)$after);
        $count = $until === false
            ? PHP_INT_MAX
            : max(0, (int)$until - $offset);

        $ids = self::mutateState($queue, function (array $state) use ($offset, $count): array {
            $ids = array_splice($state['ready'], $offset, $count);
            foreach ($ids as $id) {
                unset($state['attempts'][$id], $state['leases'][$id], $state['payloads'][$id]);
                $state['failed'] = self::withoutId($state['failed'], $id);
            }

            return [$state, $ids];
        });

        $items = (new Arr($ids))->map(function (string $id) use ($queue) {
            $payload = self::instance()->get(self::payloadKey($queue, $id));
            self::instance()->delete(self::payloadKey($queue, $id));

            return $payload === false
                ? null
                : unserialize($payload, ['allowed_classes' => true]);
        })->filter(fn ($item) => $item !== null)->val();

        return new Collection($items);
    }

    public static function claim($queue, int $leaseSeconds = 0, ?int $now = null): ?QueueReceipt
    {
        self::assertQueue($queue);
        $now ??= time();
        $leaseSeconds = $leaseSeconds > 0
            ? $leaseSeconds
            : (int)self::$_config['lease_seconds'];

        self::recover($queue, $now);

        while (true) {
            $claim = self::mutateState($queue, function (array $state) use ($now, $leaseSeconds): array {
                $id = self::$_mode === self::FILO
                    ? array_pop($state['ready'])
                    : array_shift($state['ready']);

                if ($id === null) {
                    return [$state, null];
                }

                $attempts = ((int)($state['attempts'][$id] ?? 0)) + 1;
                $token = bin2hex(random_bytes(16));
                $expiresAt = $now + $leaseSeconds;
                $state['attempts'][$id] = $attempts;
                $state['leases'][$id] = [
                    'token' => $token,
                    'expires_at' => $expiresAt,
                ];

                return [$state, [$id, $attempts, $expiresAt, $token]];
            });

            if ($claim === null) {
                return null;
            }

            [$id, $attempts, $expiresAt, $token] = $claim;
            $payload = self::instance()->get(self::payloadKey($queue, $id));
            if ($payload !== false) {
                return new QueueReceipt(
                    $id,
                    unserialize($payload, ['allowed_classes' => true]),
                    $attempts,
                    $expiresAt,
                    $token,
                );
            }

            self::acknowledge($queue, new QueueReceipt($id, null, $attempts, $expiresAt, $token));
        }
    }

    public static function acknowledge($queue, QueueReceipt $receipt): bool
    {
        self::assertQueue($queue);
        $acknowledged = self::mutateState($queue, function (array $state) use ($receipt): array {
            if (!self::ownsLease($state, $receipt)) {
                return [$state, false];
            }

            unset(
                $state['attempts'][$receipt->id],
                $state['leases'][$receipt->id],
                $state['payloads'][$receipt->id]
            );

            return [$state, true];
        });

        if ($acknowledged) {
            self::instance()->delete(self::payloadKey($queue, $receipt->id));
        }

        return $acknowledged;
    }

    public static function release($queue, QueueReceipt $receipt): bool
    {
        self::assertQueue($queue);

        return self::mutateState($queue, function (array $state) use ($receipt): array {
            if (!self::ownsLease($state, $receipt)) {
                return [$state, false];
            }

            unset($state['leases'][$receipt->id]);
            self::returnToQueue($state, $receipt->id);

            return [$state, true];
        });
    }

    public static function recover($queue, ?int $now = null): int
    {
        self::assertQueue($queue);
        $now ??= time();

        return self::mutateState($queue, function (array $state) use ($now): array {
            $recovered = 0;
            foreach ($state['leases'] as $id => $lease) {
                if ((int)$lease['expires_at'] > $now) {
                    continue;
                }

                unset($state['leases'][$id]);
                if (isset($state['payloads'][$id])) {
                    self::returnToQueue($state, $id);
                    $recovered++;
                }
            }

            return [$state, $recovered];
        });
    }

    public static function failed($queue, int $limit = 100): Collection
    {
        self::assertQueue($queue);
        [$state] = self::readState($queue);
        $ids = (new Arr($state['failed']))->slice(0, max(0, $limit))->val();
        $items = (new Arr($ids))->map(function (string $id) use ($queue, $state) {
            $payload = self::instance()->get(self::payloadKey($queue, $id));
            if ($payload === false) {
                return null;
            }

            return [
                'id' => $id,
                'payload' => unserialize($payload, ['allowed_classes' => true]),
                'attempts' => (int)($state['attempts'][$id] ?? 0),
            ];
        })->filter(fn ($item) => $item !== null)->val();

        return new Collection($items);
    }

    public static function retryFailed($queue, string $id): bool
    {
        self::assertQueue($queue);

        return self::mutateState($queue, function (array $state) use ($id): array {
            if (!in_array($id, $state['failed'], true) || !isset($state['payloads'][$id])) {
                return [$state, false];
            }

            $state['failed'] = self::withoutId($state['failed'], $id);
            $state['attempts'][$id] = 0;
            self::requeue($state['ready'], $id);

            return [$state, true];
        });
    }

    public static function discardFailed($queue, string $id): bool
    {
        self::assertQueue($queue);
        $discarded = self::mutateState($queue, function (array $state) use ($id): array {
            if (!in_array($id, $state['failed'], true)) {
                return [$state, false];
            }

            $state['failed'] = self::withoutId($state['failed'], $id);
            unset($state['attempts'][$id], $state['leases'][$id], $state['payloads'][$id]);

            return [$state, true];
        });

        if ($discarded) {
            self::instance()->delete(self::payloadKey($queue, $id));
        }

        return $discarded;
    }

    public static function purge($queue): int
    {
        self::assertQueue($queue);
        [$state] = self::readState($queue);
        $deleted = 0;

        foreach (array_keys($state['payloads']) as $id) {
            $deleted += self::instance()->delete(self::payloadKey($queue, $id)) ? 1 : 0;
        }

        $deleted += self::instance()->delete(self::stateKey($queue)) ? 1 : 0;

        return $deleted;
    }

    private static function instance(): object
    {
        if (self::$_stack === null) {
            self::init();
        }

        return self::$_stack;
    }

    private static function init(): void
    {
        if (!class_exists(Memcached::class)) {
            throw new RuntimeException('The ext-memcached extension is required for MemQueue.');
        }

        $stack = new Memcached();
        Str::make(self::$_memq_pool)->split(',')->each(function ($server) use ($stack): void {
            [$host, $port] = Str::make($server)->split(':')->val();
            $stack->addServer($host, (int)$port);
        });
        self::$_stack = $stack;
    }

    private static function mutateState(string $queue, callable $mutator): mixed
    {
        $key = self::stateKey($queue);
        $retries = max(1, (int)self::$_config['cas_retries']);

        for ($attempt = 0; $attempt < $retries; $attempt++) {
            [$state, $casToken, $exists] = self::readState($queue);
            [$nextState, $result] = $mutator($state);
            $saved = $exists
                ? self::instance()->cas($casToken, $key, $nextState, self::MEMQ_TTL)
                : self::instance()->add($key, $nextState, self::MEMQ_TTL);

            if ($saved) {
                return $result;
            }
        }

        throw new RuntimeException('Memcached queue state could not be updated after repeated CAS conflicts.');
    }

    private static function readState(string $queue): array
    {
        $stack = self::instance();
        if (method_exists($stack, 'getWithCas')) {
            [$state, $casToken] = $stack->getWithCas(self::stateKey($queue));
        } else {
            $item = $stack->get(self::stateKey($queue), null, Memcached::GET_EXTENDED);
            $state = is_array($item) ? ($item['value'] ?? false) : false;
            $casToken = is_array($item) ? ($item['cas'] ?? null) : null;
        }

        if ($state === false) {
            if ($stack->getResultCode() !== self::RESULT_NOT_FOUND) {
                throw new RuntimeException('Memcached queue state could not be read.');
            }

            return [self::emptyState(), null, false];
        }

        if (!is_array($state)) {
            throw new RuntimeException('Memcached queue state is invalid.');
        }

        return [self::normalizeState($state), $casToken, true];
    }

    private static function emptyState(): array
    {
        return [
            'ready' => [],
            'attempts' => [],
            'leases' => [],
            'failed' => [],
            'payloads' => [],
        ];
    }

    private static function normalizeState(array $state): array
    {
        return (new Arr(self::emptyState()))->merge($state)->val();
    }

    private static function returnToQueue(array &$state, string $id): void
    {
        if ((int)($state['attempts'][$id] ?? 0) >= (int)self::$_config['max_attempts']) {
            $state['failed'][] = $id;
            return;
        }

        self::requeue($state['ready'], $id);
    }

    private static function requeue(array &$ready, string $id): void
    {
        if (self::$_mode === self::FILO) {
            $ready[] = $id;
            return;
        }

        array_unshift($ready, $id);
    }

    private static function ownsLease(array $state, QueueReceipt $receipt): bool
    {
        return isset($state['leases'][$receipt->id])
            && $state['leases'][$receipt->id]['token'] === $receipt->token;
    }

    private static function withoutId(array $ids, string $id): array
    {
        return (new Arr($ids))->filter(fn ($candidate) => $candidate !== $id)->val();
    }

    private static function stateKey(string $queue): string
    {
        return self::keyBase($queue) . ':state';
    }

    private static function payloadKey(string $queue, string $id): string
    {
        return self::keyBase($queue) . ':payload:' . $id;
    }

    private static function keyBase(string $queue): string
    {
        return (string)self::$_config['queue_prefix'] . Hash::value($queue);
    }

    private static function assertQueue(mixed $queue): void
    {
        if (!is_string($queue) || trim($queue) === '') {
            throw new InvalidArgumentException('Memcached queue names must be non-empty strings.');
        }
    }
}
