<?php

namespace BlueFission\Tests\Support;

use RuntimeException;

final class FakeMongoClient
{
    public array $selectedDatabases = [];

    public function __construct(private array $databases = [])
    {
    }

    public function selectDatabase(string $name): FakeMongoDatabase
    {
        $this->selectedDatabases[] = $name;
        return $this->databases[$name] ??= new FakeMongoDatabase();
    }
}

final class FakeMongoDatabase
{
    public array $selectedCollections = [];
    public array $commands = [];
    public ?string $commandFailure = null;

    public function __construct(private array $collections = [])
    {
    }

    public function selectCollection(string $name): FakeMongoCollection
    {
        $this->selectedCollections[] = $name;
        return $this->collections[$name] ??= new FakeMongoCollection();
    }

    public function command(array $command): array
    {
        if ($this->commandFailure !== null) {
            throw new RuntimeException($this->commandFailure);
        }

        $this->commands[] = $command;
        return ['ok' => 1];
    }
}

final class FakeMongoCollection
{
    public array $calls = [];
    public array $failures = [];

    public function find(array $filter): array
    {
        return $this->invoke('find', [$filter], [['matched' => true]]);
    }

    public function insertOne(array $document): FakeMongoWriteResult
    {
        return $this->invoke('insertOne', [$document], new FakeMongoWriteResult('inserted-one'));
    }

    public function insertMany(array $documents): FakeMongoWriteResult
    {
        $ids = [];
        foreach ($documents as $index => $_document) {
            $ids[$index] = 'inserted-' . ($index + 1);
        }

        return $this->invoke('insertMany', [$documents], new FakeMongoWriteResult(null, $ids));
    }

    public function updateMany(array $filter, array $update): FakeMongoWriteResult
    {
        return $this->invoke('updateMany', [$filter, $update], new FakeMongoWriteResult());
    }

    public function replaceOne(array $filter, array $replacement): FakeMongoWriteResult
    {
        return $this->invoke('replaceOne', [$filter, $replacement], new FakeMongoWriteResult());
    }

    public function deleteMany(array $filter): FakeMongoWriteResult
    {
        return $this->invoke('deleteMany', [$filter], new FakeMongoWriteResult());
    }

    private function invoke(string $method, array $arguments, mixed $result): mixed
    {
        $this->calls[] = ['method' => $method, 'arguments' => $arguments];

        if (isset($this->failures[$method])) {
            throw new RuntimeException($this->failures[$method]);
        }

        return $result;
    }
}

final class FakeMongoWriteResult
{
    public function __construct(
        private mixed $insertedId = null,
        private array $insertedIds = [],
        private bool $acknowledged = true,
    ) {
    }

    public function isAcknowledged(): bool
    {
        return $this->acknowledged;
    }

    public function getInsertedId(): mixed
    {
        return $this->insertedId;
    }

    public function getInsertedIds(): array
    {
        return $this->insertedIds;
    }
}
