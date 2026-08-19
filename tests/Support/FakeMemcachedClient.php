<?php

namespace BlueFission\Tests\Support;

final class FakeMemcachedClient
{
    private const RESULT_SUCCESS = 0;
    private const RESULT_NOT_FOUND = 16;

    private array $values = [];
    private array $versions = [];
    private int $resultCode = self::RESULT_SUCCESS;

    public function get(string $key, mixed $callback = null, mixed &$casToken = null): mixed
    {
        if (!array_key_exists($key, $this->values)) {
            $this->resultCode = self::RESULT_NOT_FOUND;
            $casToken = null;
            return false;
        }

        $this->resultCode = self::RESULT_SUCCESS;
        $casToken = $this->versions[$key];
        return $this->values[$key];
    }

    public function getWithCas(string $key): array
    {
        $casToken = null;
        $value = $this->get($key, null, $casToken);

        return [$value, $casToken];
    }

    public function set(string $key, mixed $value, int $expiration = 0): bool
    {
        $this->values[$key] = $value;
        $this->versions[$key] = ($this->versions[$key] ?? 0) + 1;
        $this->resultCode = self::RESULT_SUCCESS;
        return true;
    }

    public function add(string $key, mixed $value, int $expiration = 0): bool
    {
        if (array_key_exists($key, $this->values)) {
            return false;
        }

        return $this->set($key, $value, $expiration);
    }

    public function cas(mixed $casToken, string $key, mixed $value, int $expiration = 0): bool
    {
        if (!array_key_exists($key, $this->values) || $this->versions[$key] !== $casToken) {
            return false;
        }

        return $this->set($key, $value, $expiration);
    }

    public function delete(string $key): bool
    {
        if (!array_key_exists($key, $this->values)) {
            return false;
        }

        unset($this->values[$key], $this->versions[$key]);
        $this->resultCode = self::RESULT_SUCCESS;
        return true;
    }

    public function getResultCode(): int
    {
        return $this->resultCode;
    }
}
