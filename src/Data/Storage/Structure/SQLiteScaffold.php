<?php

namespace BlueFission\Data\Storage\Structure;

use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\Connections\Database\SQLiteLink;
use BlueFission\Data\Storage\SQLite;

/**
 * Class SQLiteScaffold
 *
 * @package BlueFission\Data\Storage\Structure
 */
class SQLiteScaffold implements IScaffold
{
    /**
     * Creates a new SQLite table using the entity name and a processor to configure the structure.
     *
     * @param string $entity The name of the table to be created.
     * @param callable $processor The function used to configure the structure of the table.
     * @param SQLiteLink|null $connection An already-open connection borrowed from its owner.
     */
    public static function create($entity, callable $processor, ?SQLiteLink $connection = null)
    {

        $refFunction = new \ReflectionFunction($processor);
        $parameters = $refFunction->getParameters();
        $type = $parameters[0]->getType()->getName() ?? Structure::class;

        $structure = new $type($entity);
        call_user_func_array($processor, [$structure]);
        $query = $structure->build();

        if ($connection) {
            $connection->query($query);
            $connection->perform(
                $connection->status() === SQLiteLink::STATUS_SUCCESS
                    ? Event::CREATED
                    : [Event::ACTION_FAILED, Event::FAILURE],
                new Meta(
                    when: Action::CREATE,
                    info: $connection->status(),
                    data: ['entity' => $entity, 'operation' => 'create']
                )
            );
            return;
        }

        $sqlite = new SQLite(['location' => null, 'name' => $entity]);
        $sqlite->activate();
        $sqlite->run($query);
        print("Creating {$entity}. " . $sqlite->status(). "\n");
    }

    /**
     * Alters an existing SQLite table using the entity name and a processor to configure the structure.
     *
     * @param string $entity The name of the table to be altered.
     * @param callable $processor The function used to configure the structure of the table.
     */
    public static function alter($entity, callable $processor)
    {
        // SQLite has limited ALTER support; this method can be customized if needed
    }

    /**
     * Deletes an existing SQLite table using the entity name.
     *
     * @param string $entity The name of the table to be deleted.
     * @param SQLiteLink|null $connection An already-open connection borrowed from its owner.
     */
    public static function delete($entity, ?SQLiteLink $connection = null)
    {
        $query = "DROP TABLE IF EXISTS `{$entity}`";
        if ($connection) {
            $connection->query($query);
            $connection->perform(
                $connection->status() === SQLiteLink::STATUS_SUCCESS
                    ? Event::DELETED
                    : [Event::ACTION_FAILED, Event::FAILURE],
                new Meta(
                    when: Action::DELETE,
                    info: $connection->status(),
                    data: ['entity' => $entity, 'operation' => 'delete']
                )
            );
            return;
        }

        $sqlite = new SQLite(['location' => null, 'name' => $entity]);
        $sqlite->activate();
        $sqlite->run($query);
        print("Dropping {$entity}. " . $sqlite->status(). "\n");
    }
}
