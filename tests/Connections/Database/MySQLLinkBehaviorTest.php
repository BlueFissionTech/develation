<?php

namespace BlueFission\Tests\Connections\Database;

use BlueFission\Connections\Connection;
use BlueFission\Connections\Database\MySQLLink;
use BlueFission\Tests\Support\FakeMySQLClient;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class MySQLLinkBehaviorTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->databaseProperty()->setValue(null, []);
    }

    public function testSuccessfulInsertReportsSuccess(): void
    {
        $client = new FakeMySQLClient();
        $link = $this->linkUsing($client);

        $link->query(['event_key' => 'event-1', 'payload' => '{"status":"ready"}']);

        $this->assertTrue($link->result());
        $this->assertSame(Connection::STATUS_SUCCESS, $link->status());
    }

    public function testFailedInsertPreservesDatabaseError(): void
    {
        $client = new FakeMySQLClient(false);
        $client->error = "Duplicate entry 'event-1' for key 'event_key'";
        $link = $this->linkUsing($client);

        $link->query(['event_key' => 'event-1', 'payload' => '{"status":"conflict"}']);

        $this->assertFalse($link->result());
        $this->assertSame($client->error, $link->status());
        $this->assertNotSame(Connection::STATUS_SUCCESS, $link->status());
    }

    public function testFailedUpdatePreservesDatabaseError(): void
    {
        $client = new FakeMySQLClient(false);
        $client->error = 'Lock wait timeout exceeded';
        $link = $this->linkUsing($client);

        $link->query(['id' => 1, 'payload' => '{"status":"retry"}']);

        $this->assertFalse($link->result());
        $this->assertSame($client->error, $link->status());
        $this->assertNotSame(Connection::STATUS_SUCCESS, $link->status());
    }

    public function testOpenReportsConnectedWhenReusingSharedClient(): void
    {
        $client = new FakeMySQLClient();
        $this->databaseProperty()->setValue(null, [$client]);
        $link = new MySQLLink();

        $this->assertSame(Connection::STATUS_NOTCONNECTED, $link->status());

        $link->open();

        $this->assertSame($client, $link->connection());
        $this->assertSame(Connection::STATUS_CONNECTED, $link->status());
    }

    private function linkUsing(FakeMySQLClient $client): MySQLLink
    {
        $link = new MySQLLink([
            'table' => 'events',
            'key' => 'id',
        ]);

        $connection = new ReflectionProperty(MySQLLink::class, '_connection');
        $connection->setValue($link, $client);
        $this->databaseProperty()->setValue(null, [$client]);

        return $link;
    }

    private function databaseProperty(): ReflectionProperty
    {
        return new ReflectionProperty(MySQLLink::class, '_database');
    }
}
