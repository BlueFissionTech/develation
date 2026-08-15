<?php

namespace BlueFission\Data\Queues;

use BlueFission\Arr;
use BlueFission\Collections\Collection;
use BlueFission\Connections\Redis as RedisConnection;
use InvalidArgumentException;
use RuntimeException;

class RedisQueue extends Queue implements IReliableQueue
{
    private const ENQUEUE_SCRIPT = <<<'LUA'
-- develation:enqueue
redis.call('HSET', KEYS[2], ARGV[1], ARGV[2])
redis.call('HSET', KEYS[3], ARGV[1], 0)
redis.call('RPUSH', KEYS[1], ARGV[1])
return ARGV[1]
LUA;

    private const CLAIM_SCRIPT = <<<'LUA'
-- develation:claim
local id = redis.call(ARGV[1], KEYS[1])
while id do
    local payload = redis.call('HGET', KEYS[2], id)
    if payload then
        local attempts = redis.call('HINCRBY', KEYS[3], id, 1)
        redis.call('HSET', KEYS[5], id, ARGV[2])
        redis.call('ZADD', KEYS[4], ARGV[3], id)
        return {id, payload, tostring(attempts), ARGV[3], ARGV[2]}
    end
    id = redis.call(ARGV[1], KEYS[1])
end
return {}
LUA;

    private const ACKNOWLEDGE_SCRIPT = <<<'LUA'
-- develation:acknowledge
if redis.call('HGET', KEYS[5], ARGV[1]) ~= ARGV[2] then
    return 0
end
redis.call('HDEL', KEYS[2], ARGV[1])
redis.call('HDEL', KEYS[3], ARGV[1])
redis.call('HDEL', KEYS[5], ARGV[1])
redis.call('ZREM', KEYS[4], ARGV[1])
return 1
LUA;

    private const RELEASE_SCRIPT = <<<'LUA'
-- develation:release
if redis.call('HGET', KEYS[5], ARGV[1]) ~= ARGV[2] then
    return 0
end
redis.call('HDEL', KEYS[5], ARGV[1])
redis.call('ZREM', KEYS[4], ARGV[1])
if redis.call('HEXISTS', KEYS[2], ARGV[1]) == 1 then
    local attempts = tonumber(redis.call('HGET', KEYS[3], ARGV[1]) or '0')
    if attempts >= tonumber(ARGV[4]) then
        redis.call('RPUSH', KEYS[6], ARGV[1])
    else
        redis.call(ARGV[3], KEYS[1], ARGV[1])
    end
end
return 1
LUA;

    private const RECOVER_SCRIPT = <<<'LUA'
-- develation:recover
local ids = redis.call('ZRANGEBYSCORE', KEYS[4], '-inf', ARGV[1])
local recovered = 0
for _, id in ipairs(ids) do
    if redis.call('ZREM', KEYS[4], id) == 1 then
        redis.call('HDEL', KEYS[5], id)
        if redis.call('HEXISTS', KEYS[2], id) == 1 then
            local attempts = tonumber(redis.call('HGET', KEYS[3], id) or '0')
            if attempts >= tonumber(ARGV[2]) then
                redis.call('RPUSH', KEYS[6], id)
            else
                redis.call(ARGV[3], KEYS[1], id)
            end
            recovered = recovered + 1
        end
    end
end
return recovered
LUA;

    private const RETRY_FAILED_SCRIPT = <<<'LUA'
-- develation:retry-failed
if redis.call('LREM', KEYS[6], 1, ARGV[1]) == 0 then
    return 0
end
if redis.call('HEXISTS', KEYS[2], ARGV[1]) == 0 then
    return 0
end
redis.call('HSET', KEYS[3], ARGV[1], 0)
redis.call(ARGV[2], KEYS[1], ARGV[1])
return 1
LUA;

    private const DISCARD_FAILED_SCRIPT = <<<'LUA'
-- develation:discard-failed
if redis.call('LREM', KEYS[6], 1, ARGV[1]) == 0 then
    return 0
end
redis.call('HDEL', KEYS[2], ARGV[1])
redis.call('HDEL', KEYS[3], ARGV[1])
redis.call('HDEL', KEYS[5], ARGV[1])
redis.call('ZREM', KEYS[4], ARGV[1])
return 1
LUA;

    private static ?RedisConnection $_connection = null;

    private static array $_config = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'timeout' => 2.5,
        'username' => null,
        'password' => null,
        'database' => 0,
        'prefix' => '',
        'queue_prefix' => 'develation:queue:',
        'lease_seconds' => 60,
        'max_attempts' => 3,
    ];

    public static function configure(array $config): void
    {
        self::$_config = (new Arr(self::$_config))->merge($config)->val();
    }

    public static function setConnection(RedisConnection $connection): void
    {
        self::$_connection = $connection;
    }

    public static function reset(): void
    {
        self::$_connection = null;
        self::$_config = [
            'host' => '127.0.0.1',
            'port' => 6379,
            'timeout' => 2.5,
            'username' => null,
            'password' => null,
            'database' => 0,
            'prefix' => '',
            'queue_prefix' => 'develation:queue:',
            'lease_seconds' => 60,
            'max_attempts' => 3,
        ];
        self::$_mode = self::FIFO;
    }

    public static function isEmpty($queue)
    {
        self::assertQueue($queue);
        self::recover($queue);

        return (int)self::connection()->command('lLen', [self::keys($queue)[0]]) === 0;
    }

    public static function enqueue($queue, $item)
    {
        self::assertQueue($queue);
        $id = bin2hex(random_bytes(16));
        $keys = self::keys($queue);

        return self::evaluate(self::ENQUEUE_SCRIPT, array_slice($keys, 0, 3), [
            $id,
            serialize($item),
        ]);
    }

    public static function dequeue($queue, $after = false, $until = false)
    {
        if ($after === false && $until === false) {
            $receipt = self::claim($queue);
            if ($receipt === null) {
                return false;
            }

            if (!self::acknowledge($queue, $receipt)) {
                throw new RuntimeException('Redis queue acknowledgement failed.');
            }

            return $receipt->payload;
        }

        $count = $until === false
            ? max(0, (int)$after)
            : max(0, (int)$until - (int)$after);
        $items = [];

        while ($count > 0) {
            $item = self::dequeue($queue);
            if ($item === false) {
                break;
            }
            $items[] = $item;
            $count--;
        }

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

        $result = self::evaluate(self::CLAIM_SCRIPT, array_slice(self::keys($queue), 0, 5), [
            self::popCommand(),
            bin2hex(random_bytes(16)),
            $now + $leaseSeconds,
        ]);

        if (!Arr::is($result) || count($result) < 5) {
            return null;
        }

        return new QueueReceipt(
            (string)$result[0],
            unserialize($result[1], ['allowed_classes' => true]),
            (int)$result[2],
            (int)$result[3],
            (string)$result[4],
        );
    }

    public static function acknowledge($queue, QueueReceipt $receipt): bool
    {
        self::assertQueue($queue);

        return (int)self::evaluate(
            self::ACKNOWLEDGE_SCRIPT,
            array_slice(self::keys($queue), 0, 5),
            [$receipt->id, $receipt->token]
        ) === 1;
    }

    public static function release($queue, QueueReceipt $receipt): bool
    {
        self::assertQueue($queue);

        return (int)self::evaluate(
            self::RELEASE_SCRIPT,
            self::keys($queue),
            [
                $receipt->id,
                $receipt->token,
                self::requeueCommand(),
                (int)self::$_config['max_attempts'],
            ]
        ) === 1;
    }

    public static function recover($queue, ?int $now = null): int
    {
        self::assertQueue($queue);

        return (int)self::evaluate(self::RECOVER_SCRIPT, self::keys($queue), [
            $now ?? time(),
            (int)self::$_config['max_attempts'],
            self::requeueCommand(),
        ]);
    }

    public static function failed($queue, int $limit = 100): Collection
    {
        self::assertQueue($queue);
        $keys = self::keys($queue);
        $ids = self::connection()->command('lRange', [$keys[5], 0, max(0, $limit - 1)]);

        if (!Arr::is($ids) || Arr::isEmpty($ids)) {
            return new Collection([]);
        }

        $payloads = self::connection()->command('hMGet', [$keys[1], $ids]);
        $attempts = self::connection()->command('hMGet', [$keys[2], $ids]);
        $items = (new Arr($ids))->map(function ($id) use ($payloads, $attempts) {
            $payload = $payloads[$id] ?? false;
            if (!is_string($payload)) {
                return null;
            }

            return [
                'id' => $id,
                'payload' => unserialize($payload, ['allowed_classes' => true]),
                'attempts' => (int)($attempts[$id] ?? 0),
            ];
        })->filter(fn ($item) => $item !== null)->val();

        return new Collection($items);
    }

    public static function retryFailed($queue, string $id): bool
    {
        self::assertQueue($queue);

        return (int)self::evaluate(
            self::RETRY_FAILED_SCRIPT,
            self::keys($queue),
            [$id, self::requeueCommand()]
        ) === 1;
    }

    public static function discardFailed($queue, string $id): bool
    {
        self::assertQueue($queue);

        return (int)self::evaluate(
            self::DISCARD_FAILED_SCRIPT,
            self::keys($queue),
            [$id]
        ) === 1;
    }

    public static function purge($queue): int
    {
        self::assertQueue($queue);

        return (int)self::connection()->command('del', [self::keys($queue)]);
    }

    private static function connection(): RedisConnection
    {
        if (self::$_connection === null) {
            self::$_connection = new RedisConnection(self::$_config);
        }

        if (self::$_connection->connection() === null) {
            self::$_connection->open();
        }

        return self::$_connection;
    }

    private static function evaluate(string $script, array $keys, array $arguments): mixed
    {
        return self::connection()->command('eval', [
            $script,
            array_merge($keys, $arguments),
            count($keys),
        ]);
    }

    private static function keys(string $queue): array
    {
        $base = self::connection()->key((string)self::$_config['queue_prefix'] . $queue);

        return [
            "{$base}:ready",
            "{$base}:payloads",
            "{$base}:attempts",
            "{$base}:leases",
            "{$base}:reservations",
            "{$base}:failed",
        ];
    }

    private static function popCommand(): string
    {
        return self::$_mode === self::FILO ? 'RPOP' : 'LPOP';
    }

    private static function requeueCommand(): string
    {
        return self::$_mode === self::FILO ? 'RPUSH' : 'LPUSH';
    }

    private static function assertQueue(mixed $queue): void
    {
        if (!is_string($queue) || trim($queue) === '') {
            throw new InvalidArgumentException('Redis queue names must be non-empty strings.');
        }
    }
}
