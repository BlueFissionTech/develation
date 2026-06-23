<?php

declare(strict_types=1);

require __DIR__ . '/../support.php';

use BlueFission\Arr;
use BlueFission\Date;
use BlueFission\Flag;
use BlueFission\Net\HTTP;
use BlueFission\Obj;
use BlueFission\Security\Hash;
use BlueFission\Str;

$request = (new Obj())->assign([
    'method' => 'GET',
    'base_url' => 'https://api.example.test/v1',
    'resource' => 'Example Report.md',
    'params' => [
        'limit' => 3,
        'include' => 'summary',
        'active' => Flag::parseBool('yes') ? 'true' : 'false',
    ],
]);

$resource = HTTP::pathSegment((string)$request->field('resource'));
$query = HTTP::query($request->field('params'));
$url = Str::trim((string)$request->field('base_url'), '/') . '/' . $resource . '?' . $query;

$headers = Arr::make([
    HTTP::headerLine('Accept', 'application/json'),
    HTTP::headerLine('X-Request-Date', Date::formatTimestamp(time())),
    HTTP::headerLine('X-Request-Id', bf_example_id('api')),
]);

$packet = [
    'request' => [
        'method' => $request->field('method'),
        'url' => $url,
        'scheme' => HTTP::urlScheme($url),
        'host' => HTTP::urlHost($url),
        'path_segment' => $resource,
        'query' => $query,
    ],
    'headers' => $headers->val(),
    'expected_response' => [
        'status' => HTTP::statusLine(202),
        'body' => [
            'accepted' => true,
            'message' => 'queued',
        ],
    ],
];

$packet['content_id'] = Hash::contentIdValue($packet, null, 'api-example');

echo HTTP::jsonEncode($packet) . PHP_EOL;
