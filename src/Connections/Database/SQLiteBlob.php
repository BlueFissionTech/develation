<?php

namespace BlueFission\Connections\Database;

/** Explicit byte value for SQLite prepared BLOB bindings. */
final class SQLiteBlob
{
    public function __construct(private readonly string $bytes)
    {
    }

    public function bytes(): string
    {
        return $this->bytes;
    }
}
