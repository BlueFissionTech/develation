<?php

namespace BlueFission\Tests\Support;

final class FakeMySQLClient
{
    public ?string $connect_error = null;
    public string $error = '';
    public int $insert_id = 1;
    public int $thread_id = 1;

    public function __construct(private bool $queryResult = true)
    {
    }

    public function query(string $query): bool
    {
        return $this->queryResult;
    }

    public function real_escape_string(string $value): string
    {
        return addslashes($value);
    }
}
