<?php

namespace BlueFission\Tests\Support;

final class FakeRedisClient
{
    public bool $connected = false;
    public bool $closed = false;
    public array $connectionArguments = [];
    public mixed $credentials = null;
    public int $database = 0;
    public array $strings = [];
    public array $expirations = [];
    public array $lists = [];
    public array $hashes = [];
    public array $sortedSets = [];

    public function connect(
        string $host,
        int $port,
        float $timeout,
        mixed $reserved = null,
        int $retryInterval = 0,
        float $readTimeout = 0.0,
    ): bool
    {
        $this->connected = true;
        $this->connectionArguments = [$host, $port, $timeout, $reserved, $retryInterval, $readTimeout];
        return true;
    }

    public function pconnect(
        string $host,
        int $port,
        float $timeout,
        ?string $persistentId = null,
        int $retryInterval = 0,
        float $readTimeout = 0.0,
    ): bool {
        $this->connectionArguments = [
            $host,
            $port,
            $timeout,
            $persistentId,
            $retryInterval,
            $readTimeout,
        ];
        $this->connected = true;
        return true;
    }

    public function auth(mixed $credentials): bool
    {
        $this->credentials = $credentials;
        return true;
    }

    public function select(int $database): bool
    {
        $this->database = $database;
        return true;
    }

    public function close(): bool
    {
        $this->closed = true;
        $this->connected = false;
        return true;
    }

    public function set(string $key, mixed $value): bool
    {
        $this->strings[$key] = $value;
        unset($this->expirations[$key]);
        return true;
    }

    public function setEx(string $key, int $ttl, mixed $value): bool
    {
        $this->strings[$key] = $value;
        $this->expirations[$key] = $ttl;
        return true;
    }

    public function get(string $key): mixed
    {
        return $this->strings[$key] ?? false;
    }

    public function del(array|string ...$keys): int
    {
        if (count($keys) === 1 && is_array($keys[0])) {
            $keys = $keys[0];
        }

        $deleted = 0;
        foreach ($keys as $key) {
            $found = false;
            foreach (['strings', 'lists', 'hashes', 'sortedSets'] as $store) {
                if (array_key_exists($key, $this->{$store})) {
                    unset($this->{$store}[$key]);
                    $found = true;
                }
            }
            unset($this->expirations[$key]);
            $deleted += $found ? 1 : 0;
        }

        return $deleted;
    }

    public function lLen(string $key): int
    {
        return count($this->lists[$key] ?? []);
    }

    public function lRange(string $key, int $start, int $stop): array
    {
        $length = $stop < 0 ? null : $stop - $start + 1;
        return array_slice($this->lists[$key] ?? [], $start, $length);
    }

    public function hMGet(string $key, array $fields): array
    {
        $values = [];
        foreach ($fields as $field) {
            $values[$field] = $this->hashes[$key][$field] ?? false;
        }
        return $values;
    }

    public function eval(string $script, array $arguments, int $keyCount): mixed
    {
        $keys = array_slice($arguments, 0, $keyCount);
        $args = array_slice($arguments, $keyCount);

        return match (true) {
            str_contains($script, 'develation:enqueue') => $this->enqueue($keys, $args),
            str_contains($script, 'develation:claim') => $this->claim($keys, $args),
            str_contains($script, 'develation:acknowledge') => $this->acknowledge($keys, $args),
            str_contains($script, 'develation:release') => $this->release($keys, $args),
            str_contains($script, 'develation:recover') => $this->recover($keys, $args),
            str_contains($script, 'develation:retry-failed') => $this->retryFailed($keys, $args),
            str_contains($script, 'develation:discard-failed') => $this->discardFailed($keys, $args),
            default => false,
        };
    }

    private function enqueue(array $keys, array $args): string
    {
        [$id, $payload] = $args;
        $this->hashes[$keys[1]][$id] = $payload;
        $this->hashes[$keys[2]][$id] = 0;
        $this->lists[$keys[0]][] = $id;
        return $id;
    }

    private function claim(array $keys, array $args): array
    {
        [$popCommand, $token, $deadline] = $args;

        while (!empty($this->lists[$keys[0]])) {
            $id = $popCommand === 'RPOP'
                ? array_pop($this->lists[$keys[0]])
                : array_shift($this->lists[$keys[0]]);
            $payload = $this->hashes[$keys[1]][$id] ?? null;
            if ($payload === null) {
                continue;
            }

            $attempts = ((int)($this->hashes[$keys[2]][$id] ?? 0)) + 1;
            $this->hashes[$keys[2]][$id] = $attempts;
            $this->hashes[$keys[4]][$id] = $token;
            $this->sortedSets[$keys[3]][$id] = (int)$deadline;

            return [$id, $payload, (string)$attempts, (string)$deadline, $token];
        }

        return [];
    }

    private function acknowledge(array $keys, array $args): int
    {
        [$id, $token] = $args;
        if (($this->hashes[$keys[4]][$id] ?? null) !== $token) {
            return 0;
        }

        unset(
            $this->hashes[$keys[1]][$id],
            $this->hashes[$keys[2]][$id],
            $this->hashes[$keys[4]][$id],
            $this->sortedSets[$keys[3]][$id]
        );
        return 1;
    }

    private function release(array $keys, array $args): int
    {
        [$id, $token, $pushCommand, $maxAttempts] = $args;
        if (($this->hashes[$keys[4]][$id] ?? null) !== $token) {
            return 0;
        }

        unset($this->hashes[$keys[4]][$id], $this->sortedSets[$keys[3]][$id]);
        if (array_key_exists($id, $this->hashes[$keys[1]] ?? [])) {
            if ((int)$this->hashes[$keys[2]][$id] >= (int)$maxAttempts) {
                $this->lists[$keys[5]][] = $id;
            } else {
                $this->push($keys[0], $id, $pushCommand);
            }
        }
        return 1;
    }

    private function recover(array $keys, array $args): int
    {
        $now = (int)$args[0];
        $maxAttempts = (int)$args[1];
        $pushCommand = $args[2];
        $recovered = 0;

        foreach ($this->sortedSets[$keys[3]] ?? [] as $id => $deadline) {
            if ($deadline > $now) {
                continue;
            }

            unset($this->sortedSets[$keys[3]][$id], $this->hashes[$keys[4]][$id]);
            if (!array_key_exists($id, $this->hashes[$keys[1]] ?? [])) {
                continue;
            }

            if ((int)$this->hashes[$keys[2]][$id] >= $maxAttempts) {
                $this->lists[$keys[5]][] = $id;
            } else {
                $this->push($keys[0], $id, $pushCommand);
            }
            $recovered++;
        }

        return $recovered;
    }

    private function retryFailed(array $keys, array $args): int
    {
        [$id, $pushCommand] = $args;
        if (!$this->removeFromList($keys[5], $id)) {
            return 0;
        }
        if (!array_key_exists($id, $this->hashes[$keys[1]] ?? [])) {
            return 0;
        }

        $this->hashes[$keys[2]][$id] = 0;
        $this->push($keys[0], $id, $pushCommand);
        return 1;
    }

    private function discardFailed(array $keys, array $args): int
    {
        [$id] = $args;
        if (!$this->removeFromList($keys[5], $id)) {
            return 0;
        }

        unset(
            $this->hashes[$keys[1]][$id],
            $this->hashes[$keys[2]][$id],
            $this->hashes[$keys[4]][$id],
            $this->sortedSets[$keys[3]][$id]
        );
        return 1;
    }

    private function push(string $key, string $id, string $command): void
    {
        if ($command === 'RPUSH') {
            $this->lists[$key][] = $id;
            return;
        }

        array_unshift($this->lists[$key], $id);
    }

    private function removeFromList(string $key, string $id): bool
    {
        $position = array_search($id, $this->lists[$key] ?? [], true);
        if ($position === false) {
            return false;
        }

        array_splice($this->lists[$key], $position, 1);
        return true;
    }
}
