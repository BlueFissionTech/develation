<?php

use BlueFission\Connections\Database\MySQLLink;
use BlueFission\Net\HTTP;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$input = HTTP::jsonDecode((string)($argv[1] ?? ''), true, []);
$startAt = (float)($input['start_at'] ?? 0);

while (microtime(true) < $startAt) {
    usleep(1000);
}

$link = new MySQLLink($input['config'] ?? []);
$link->open();
$link->query($input['data'] ?? []);

echo HTTP::jsonEncode([
    'result' => (bool)$link->result(),
    'status' => $link->status(),
]);
