<?php

namespace BlueFission\Tests\Connections\Database;

use BlueFission\Tests\Connections\ConnectionTest;
use BlueFission\Connections\Database\MySQLLink;

class MySQLLinkTest extends ConnectionTest
{
    public static $classname = 'BlueFission\Connections\Database\MySQLLink';

    public function setUp(): void
    {
        // Set up a bunch of conditions to create an acceptable test connection here
        if (function_exists('mysql_connect')) {
            static::$canbetested = true;
        }
        parent::setUp();
    }
}
