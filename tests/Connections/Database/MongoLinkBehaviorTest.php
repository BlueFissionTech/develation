<?php

namespace BlueFission\Tests\Connections\Database;

use BlueFission\Connections\Database\MongoLink;
use BlueFission\Tests\Support\FakeMongoClient;
use BlueFission\Tests\Support\FakeMongoCollection;
use BlueFission\Tests\Support\FakeMongoDatabase;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Support/FakeMongoClient.php';

class MongoLinkBehaviorTest extends TestCase
{
    private FakeMongoClient $client;
    private FakeMongoDatabase $database;
    private FakeMongoCollection $collection;
    private MongoLink $link;

    protected function setUp(): void
    {
        $this->collection = new FakeMongoCollection();
        $this->database = new FakeMongoDatabase(['items' => $this->collection]);
        $this->client = new FakeMongoClient(['test' => $this->database]);
        $this->link = new InjectableMongoLink($this->client, [
            'target' => 'localhost',
            'database' => 'test',
            'collection' => 'items',
            'key' => '_id',
        ]);
        $this->link->open();
    }

    public function testOpenAndCloseManageInstanceConnectionState(): void
    {
        $this->assertSame(MongoLink::STATUS_CONNECTED, $this->link->status());
        $this->assertSame($this->client, $this->link->connection());
        $this->assertSame(['test'], $this->client->selectedDatabases);
        $this->assertSame([['ping' => 1]], $this->database->commands);

        $this->link->close();

        $this->assertNull($this->link->connection());
        $this->assertSame(MongoLink::STATUS_NOTCONNECTED, $this->link->status());
    }

    public function testOpenPingFailureDoesNotExposeClientAsConnected(): void
    {
        $database = new FakeMongoDatabase();
        $database->commandFailure = 'server unavailable';
        $client = new FakeMongoClient(['test' => $database]);
        $link = new InjectableMongoLink($client, [
            'database' => 'test',
            'collection' => 'items',
        ]);

        $link->open();

        $this->assertSame('server unavailable', $link->status());
        $this->assertNull($link->connection());
    }

    public function testSingleInsertIsPhp82SafeAndExposesInsertedId(): void
    {
        $this->link->query(['name' => 'Ada']);

        $this->assertSame(MongoLink::STATUS_SUCCESS, $this->link->status());
        $this->assertSame('insertOne', $this->collection->calls[0]['method']);
        $this->assertSame([['name' => 'Ada']], $this->collection->calls[0]['arguments']);
        $this->assertSame('inserted-one', $this->link->lastRow());
    }

    public function testBatchInsertUsesInsertMany(): void
    {
        $documents = [['name' => 'Ada'], ['name' => 'Grace']];

        $this->link->query($documents);

        $this->assertSame(MongoLink::STATUS_SUCCESS, $this->link->status());
        $this->assertSame('insertMany', $this->collection->calls[0]['method']);
        $this->assertSame([$documents], $this->collection->calls[0]['arguments']);
        $this->assertSame('inserted-2', $this->link->lastRow());
    }

    public function testKeyedDocumentUsesUpdateManyAndSetOperator(): void
    {
        $this->link->query(['_id' => 'item-1', 'name' => 'Updated']);

        $this->assertSame(MongoLink::STATUS_SUCCESS, $this->link->status());
        $this->assertSame('updateMany', $this->collection->calls[0]['method']);
        $this->assertSame(
            [['_id' => 'item-1'], ['$set' => ['name' => 'Updated']]],
            $this->collection->calls[0]['arguments']
        );
        $this->assertSame('item-1', $this->link->lastRow());
    }

    public function testReplaceUsesCurrentReplaceOneApi(): void
    {
        $this->link->config('replace', true);

        $this->link->query(['_id' => 'item-1', 'name' => 'Replacement']);

        $this->assertSame(MongoLink::STATUS_SUCCESS, $this->link->status());
        $this->assertSame('replaceOne', $this->collection->calls[0]['method']);
        $this->assertSame(
            [['_id' => 'item-1'], ['_id' => 'item-1', 'name' => 'Replacement']],
            $this->collection->calls[0]['arguments']
        );
    }

    public function testFindDeleteAndCommandReportSuccess(): void
    {
        $this->link->find('items', ['active' => true]);
        $this->assertSame(MongoLink::STATUS_SUCCESS, $this->link->status());
        $this->assertNull($this->link->error());
        $this->assertSame('find', $this->collection->calls[0]['method']);

        $this->link->delete('items', ['active' => false]);
        $this->assertSame(MongoLink::STATUS_SUCCESS, $this->link->status());
        $this->assertSame('deleteMany', $this->collection->calls[1]['method']);

        $this->link->query('{"ping": 1}');
        $this->assertSame(MongoLink::STATUS_SUCCESS, $this->link->status());
        $this->assertSame([['ping' => 1], ['ping' => 1]], $this->database->commands);
    }

    public function testDriverExceptionIsObservableAndNeverReportsSuccess(): void
    {
        $this->collection->failures['insertOne'] = 'duplicate key';

        $this->link->query(['name' => 'Duplicate']);

        $this->assertSame('duplicate key', $this->link->status());
        $this->assertFalse($this->link->result());
        $this->assertSame('duplicate key', $this->link->error());
    }

    public function testDatabaseSwitchSelectsDatabaseOnCurrentClient(): void
    {
        $this->link->database('archive');

        $this->assertSame('archive', $this->link->database());
        $this->assertSame(['test', 'archive'], $this->client->selectedDatabases);
        $this->assertSame(MongoLink::STATUS_CONNECTED, $this->link->status());
    }

    public function testIdentityOnlyUpdateFailsBeforeDriverDispatch(): void
    {
        $this->link->query(['_id' => 'item-1']);

        $this->assertSame('No MongoDB update fields supplied.', $this->link->status());
        $this->assertFalse($this->link->result());
        $this->assertSame([], $this->collection->calls);
    }

    public function testInvalidCommandJsonIsRejected(): void
    {
        $this->link->query('{invalid');

        $this->assertSame('Invalid MongoDB command JSON.', $this->link->status());
        $this->assertFalse($this->link->result());
    }
}

final class InjectableMongoLink extends MongoLink
{
    public function __construct(private object $testClient, array $config)
    {
        parent::__construct($config);
    }

    protected function createClient(string $uri, array $options): object
    {
        return $this->testClient;
    }
}
