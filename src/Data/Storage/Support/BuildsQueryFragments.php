<?php

namespace BlueFission\Data\Storage\Support;

use BlueFission\Arr;

/**
 * Shared SQL query-fragment helpers for storage builders.
 */
trait BuildsQueryFragments
{
    /**
     * Build an INNER JOIN fragment for related table reads/deletes.
     *
     * @param array $tables
     * @param array $conditions
     * @return string
     */
    protected function innerJoinClause(array $tables, array $conditions): string
    {
        if (Arr::count($tables) <= 1) {
            return '';
        }

        return 'INNER JOIN (' . Arr::join(Arr::slice($tables, 1), ', ') . ') ON (' . Arr::join($conditions, ' AND ') . ')';
    }

    /**
     * Append a list-backed SQL clause when values are present.
     *
     * @param string $query
     * @param string $clause
     * @param array $values
     * @param string $separator
     * @return string
     */
    protected function appendListClause(string $query, string $clause, array $values, string $separator = ', '): string
    {
        if (Arr::isEmpty($values)) {
            return $query;
        }

        return $query . ' ' . $clause . ' ' . Arr::join($values, $separator);
    }
}
