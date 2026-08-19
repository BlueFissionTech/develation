<?php

namespace BlueFission\Connections\Database;

use BlueFission\Arr;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Connections\Connection;
use BlueFission\IObj;
use BlueFission\IVal;
use BlueFission\Net\HTTP;
use BlueFission\Str;
use BlueFission\Val;
use InvalidArgumentException;
use MongoDB\BSON\Javascript;
use MongoDB\Client;
use RuntimeException;
use Throwable;

/**
 * MongoDB connection adapter using the current mongodb/mongodb client API.
 */
class MongoLink extends Connection
{
    public const INSERT = 1;
    public const UPDATE = 2;
    public const REPLACE = 3;

    protected mixed $_current = null;

    private mixed $_query = null;

    private mixed $_last_row = null;

    private ?array $_dataset = null;

    protected $_config = [
        'target' => 'localhost',
        'port' => 27017,
        'username' => '',
        'password' => '',
        'database' => '',
        'collection' => '',
        'key' => '_id',
        'replace' => false,
        'options' => [],
    ];

    protected function _open(): void
    {
        $database = (string)$this->config('database');

        try {
            if (Str::isEmpty($database)) {
                throw new InvalidArgumentException('MongoDB database name is required.');
            }

            $options = Arr::is($this->config('options'))
                ? Arr::toArray($this->config('options'))
                : [];
            $client = $this->createClient($this->connectionUri(), $options);
            $this->_current = $client;
            $this->_connection = $this->selectDatabase($client, $database);
            $this->_connection->command(['ping' => 1]);
            $status = self::STATUS_CONNECTED;
            $this->perform(
                [Event::SUCCESS, Event::CONNECTED],
                new Meta(when: Action::CONNECT, info: $status)
            );
        } catch (Throwable $exception) {
            $this->_current = null;
            $this->_connection = null;
            $status = $exception->getMessage() ?: self::STATUS_NOTCONNECTED;
            $this->perform(
                [Event::ACTION_FAILED, Event::FAILURE],
                new Meta(when: Action::CONNECT, info: $status)
            );
        }

        $this->status($status);
    }

    protected function _close(): void
    {
        $this->_connection = null;
        $this->_current = null;
        $this->perform(State::DISCONNECTED);
    }

    /**
     * @param mixed $query
     */
    public function query($query = null): IObj
    {
        $this->perform(State::PERFORMING_ACTION, new Meta(when: Action::PROCESS));

        if (Val::isNull($this->_connection)) {
            $this->_result = false;
            $this->status(self::STATUS_NOTCONNECTED);
            return $this;
        }

        if (Str::is($query)) {
            $this->_query = $query;
            $command = HTTP::jsonDecode($query, true);
            if (!Arr::is($command) || Arr::isEmpty($command)) {
                $this->_result = false;
                $this->status('Invalid MongoDB command JSON.');
                return $this;
            }

            $this->executeOperation(fn () => $this->_connection->command($command));
            return $this;
        }

        if (Val::isNotNull($query)) {
            if (!Arr::is($query)) {
                $this->_result = false;
                $this->status('MongoDB query data must be an array or JSON command.');
                return $this;
            }

            $this->_query = $query;
            if (Arr::isAssoc($query)) {
                $this->_dataset = null;
                $this->_data = Arr::toArray($query);
            } else {
                $this->_dataset = Arr::toArray($query);
                $this->_data = $this->_dataset[0] ?? [];
            }
        } else {
            $this->_dataset = null;
        }

        $collection = (string)$this->config('collection');
        $data = $this->normalizeDocument($this->_data);

        if (Str::isEmpty($collection)) {
            $this->_result = false;
            $this->status('No target collection specified.');
            return $this;
        }

        if ($this->_dataset !== null) {
            $documents = (new Arr($this->_dataset))
                ->map(fn ($document) => $this->normalizeDocument($document))
                ->val();

            if (Arr::isEmpty($documents)) {
                $this->_result = false;
                $this->status('No MongoDB documents supplied.');
                return $this;
            }

            $this->insert($collection, $documents, true);
            return $this;
        }

        if (Arr::isEmpty($data)) {
            $this->_result = false;
            $this->status('No MongoDB document supplied.');
            return $this;
        }

        $key = (string)$this->config('key');
        if (!Str::isEmpty($key) && Arr::hasKey($data, $key)) {
            $filter = [$key => $data[$key]];
            $type = $this->config('replace') ? self::REPLACE : self::UPDATE;
            $this->post($collection, $data, $filter, $type);
            return $this;
        }

        $this->post($collection, $data, null, self::INSERT);
        return $this;
    }

    public function find($collection, $data): IObj
    {
        if (Val::isNull($this->_connection)) {
            $this->_result = false;
            $this->status(self::STATUS_NOTCONNECTED);
            return $this;
        }

        $filter = $this->normalizeDocument($data);
        $this->executeOperation(
            fn () => $this->collection((string)$collection)->find($filter)
        );

        return $this;
    }

    public function delete($collection, $data): IObj
    {
        if (Val::isNull($this->_connection)) {
            $this->_result = false;
            $this->status(self::STATUS_NOTCONNECTED);
            return $this;
        }

        $filter = $this->normalizeDocument($data);
        $this->executeOperation(
            fn () => $this->collection((string)$collection)->deleteMany($filter)
        );

        return $this;
    }

    public function mapReduce($map, $reduce, $output, $action = 'replace')
    {
        if (Val::isNull($this->_connection)) {
            $this->status(self::STATUS_NOTCONNECTED);
            return false;
        }

        try {
            $command = [
                'mapreduce' => (string)$this->config('collection'),
                'map' => new Javascript($map),
                'reduce' => new Javascript($reduce),
                'query' => $this->normalizeDocument($this->_data),
                'out' => [$action => $output],
            ];
            $result = $this->_connection->command($command);
            $this->_result = $result;
            $this->status(self::STATUS_SUCCESS);
            return $result;
        } catch (Throwable $exception) {
            $this->_result = false;
            $this->status($exception->getMessage() ?: self::STATUS_FAILED);
            return false;
        }
    }

    public function connection()
    {
        return $this->_current;
    }

    public function result()
    {
        return $this->_result;
    }

    public function error()
    {
        $status = $this->status();

        return Arr::contains([
            self::STATUS_CONNECTED,
            self::STATUS_DISCONNECTED,
            self::STATUS_SUCCESS,
        ], $status, true) ? null : $status;
    }

    public function database($database = null)
    {
        if (Val::isNull($database)) {
            return $this->config('database');
        }

        $this->config('database', $database);
        if (Val::isNull($this->_current)) {
            $this->_connection = null;
            $this->status(self::STATUS_NOTCONNECTED);
            return $this;
        }

        try {
            $this->_connection = $this->selectDatabase($this->_current, (string)$database);
            $this->status(self::STATUS_CONNECTED);
        } catch (Throwable $exception) {
            $this->_connection = null;
            $this->status($exception->getMessage() ?: self::STATUS_NOTCONNECTED);
        }

        return $this;
    }

    public function lastRow()
    {
        return $this->_last_row;
    }

    public static function sanitize($value, $datetime = false)
    {
        if ($value instanceof IVal) {
            $value = $value->val();
        }

        return Str::is($value) ? Str::make($value)->trim()->val() : $value;
    }

    protected function createClient(string $uri, array $options): object
    {
        if (!class_exists(Client::class)) {
            throw new RuntimeException('mongodb/mongodb is required for MongoLink.');
        }

        return new Client($uri, $options);
    }

    protected function selectDatabase(object $client, string $database): object
    {
        if (method_exists($client, 'selectDatabase')) {
            return $client->selectDatabase($database);
        }

        return $client->{$database};
    }

    private function insert(string $collection, array $data, bool $batch = false): bool
    {
        return $this->executeOperation(function () use ($collection, $data, $batch) {
            $target = $this->collection($collection);

            if (!$batch) {
                $result = $target->insertOne($data);
                $this->_last_row = Arr::hasKey($data, (string)$this->config('key'))
                    ? $data[(string)$this->config('key')]
                    : $this->insertedId($result);
                return $result;
            }

            $results = [];
            foreach (array_chunk($data, 500) as $documents) {
                $results[] = $target->insertMany($documents);
            }
            $this->_last_row = $this->lastInsertedId($results);

            return $results;
        });
    }

    private function update(string $collection, array $data, array $filter, bool $replace = false): bool
    {
        return $this->executeOperation(function () use ($collection, $data, $filter, $replace) {
            $target = $this->collection($collection);
            $key = (string)$this->config('key');

            if ($replace) {
                $result = $target->replaceOne($filter, $data);
            } else {
                unset($data[$key]);
                if (Arr::isEmpty($data)) {
                    throw new InvalidArgumentException('No MongoDB update fields supplied.');
                }
                $update = $this->hasUpdateOperator($data) ? $data : ['$set' => $data];
                $result = $target->updateMany($filter, $update);
            }

            $this->_last_row = $filter[$key] ?? $this->_last_row;
            return $result;
        });
    }

    private function post(string $collection, array $data, ?array $filter, int $type): bool
    {
        return match ($type) {
            self::INSERT => $this->insert($collection, $data),
            self::UPDATE => $filter !== null
                ? $this->update($collection, $data, $filter)
                : $this->failOperation('No target document specified.'),
            self::REPLACE => $filter !== null
                ? $this->update($collection, $data, $filter, true)
                : $this->failOperation('No target document specified.'),
            default => $this->failOperation('MongoDB query type is not supported.'),
        };
    }

    private function executeOperation(callable $operation): bool
    {
        try {
            $result = $operation();
            if (!$this->operationSucceeded($result)) {
                throw new RuntimeException('MongoDB operation was not acknowledged.');
            }

            $this->_result = $result;
            $this->perform(
                [Event::SUCCESS, Event::COMPLETE, Event::PROCESSED],
                new Meta(when: Action::PROCESS, data: $result, info: self::STATUS_SUCCESS)
            );
            $this->status(self::STATUS_SUCCESS);
            return true;
        } catch (Throwable $exception) {
            $status = $exception->getMessage() ?: self::STATUS_FAILED;
            $this->_result = false;
            $this->perform(
                [Event::ACTION_FAILED, Event::FAILURE],
                new Meta(when: Action::PROCESS, info: $status)
            );
            $this->status($status);
            return false;
        }
    }

    private function failOperation(string $status): bool
    {
        $this->_result = false;
        $this->status($status);
        return false;
    }

    private function operationSucceeded(mixed $result): bool
    {
        if (Arr::is($result) && Arr::make($result)->isIndexed()) {
            if (Arr::isEmpty($result)) {
                return true;
            }

            return (new Arr($result))
                ->filter(fn ($item) => !$this->operationSucceeded($item))
                ->isEmpty();
        }

        if (is_object($result) && method_exists($result, 'isAcknowledged')) {
            return $result->isAcknowledged();
        }

        return $result !== false && $result !== null;
    }

    private function collection(string $collection): object
    {
        if (Str::isEmpty($collection)) {
            throw new InvalidArgumentException('MongoDB collection name is required.');
        }

        if (method_exists($this->_connection, 'selectCollection')) {
            return $this->_connection->selectCollection($collection);
        }

        return $this->_connection->{$collection};
    }

    private function normalizeDocument(mixed $document): array
    {
        if ($document instanceof IVal) {
            $document = $document->val();
        }

        if (is_object($document)) {
            $document = get_object_vars($document);
        }

        return Arr::is($document) ? Arr::toArray($document) : [];
    }

    private function hasUpdateOperator(array $data): bool
    {
        return !(new Arr(Arr::keys($data)))
            ->filter(fn ($key) => Str::make((string)$key)->startsWith('$'))
            ->isEmpty();
    }

    private function insertedId(mixed $result): mixed
    {
        return is_object($result) && method_exists($result, 'getInsertedId')
            ? $result->getInsertedId()
            : null;
    }

    private function lastInsertedId(array $results): mixed
    {
        $lastId = null;

        foreach ($results as $result) {
            if (!is_object($result) || !method_exists($result, 'getInsertedIds')) {
                continue;
            }

            $ids = $result->getInsertedIds();
            if (!Arr::isEmpty($ids)) {
                $lastId = Arr::make($ids)->pop();
            }
        }

        return $lastId;
    }

    private function connectionUri(): string
    {
        $target = Str::make((string)$this->config('target'))->trim()->val();
        if (Str::make($target)->startsWith('mongodb://') || Str::make($target)->startsWith('mongodb+srv://')) {
            return $target;
        }

        $target = Str::isEmpty($target) ? 'localhost' : $target;
        $port = (int)$this->config('port');
        $address = preg_match('/:\d+$/', $target) ? $target : $target . ':' . $port;
        $username = (string)$this->config('username');
        $password = (string)$this->config('password');
        $credentials = Str::isEmpty($username)
            ? ''
            : rawurlencode($username) . ':' . rawurlencode($password) . '@';

        return 'mongodb://' . $credentials . $address;
    }
}
