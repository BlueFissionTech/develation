<?php

declare(strict_types=1);

require __DIR__ . '/../support.php';

use BlueFission\Arr;
use BlueFission\Data\File;
use BlueFission\Data\FileSystem;
use BlueFission\Date;
use BlueFission\Flag;
use BlueFission\Net\HTTP;
use BlueFission\Num;
use BlueFission\Security\Hash;
use BlueFission\Str;

$fixtureDir = __DIR__ . DIRECTORY_SEPARATOR . 'fixtures';
$namesPath = $fixtureDir . DIRECTORY_SEPARATOR . 'names.txt';

$filesystem = new FileSystem($namesPath);
$names = Arr::make($filesystem->lines("\n"))
    ->filter(fn (string $name): bool => Str::isNotEmpty(Str::trim($name)))
    ->map(fn (string $name): string => Str::trim($name))
    ->values();

$angles = Arr::make([0, 45, 90, 180])
    ->map(function (int $degrees): array {
        $radians = Num::deg2rad($degrees);

        return [
            'degrees' => $degrees,
            'radians' => Num::round($radians, 6),
            'sin' => Num::round(Num::sin($radians), 6),
            'cos' => Num::round(Num::cos($radians), 6),
        ];
    })
    ->values()
    ->val();

$directory = new FileSystem([
    'root' => $fixtureDir,
    'filter' => [],
    'doNotConfirm' => true,
]);

$report = [
    'title' => Str::strRepeat('=', 3) . ' DevElation helper workflow ' . Str::strRepeat('=', 3),
    'processed_on' => Date::formatTimestamp(time()),
    'source_file_exists' => (new File())->exists($namesPath),
    'source_file_reachable' => (new File())->isReachable($namesPath),
    'fixture_entries' => $directory->entries(),
    'name_count' => Arr::count($names->val()),
    'names_latest_first' => $names->reverse()->val(),
    'admin_match' => Str::match('Admin', 'admin', Str::IGNORE_CASE),
    'enabled_flag' => Flag::parseBool('yes'),
    'status_line' => HTTP::statusLine(200),
    'encoded_path_segment' => HTTP::pathSegment('Example Report.md'),
    'url_host' => HTTP::urlHost('https://example.test:8443/docs?tab=api'),
    'content_id' => Hash::contentIdValue($names->val(), null, 'example'),
    'angles' => $angles,
];

echo HTTP::jsonEncode($report) . PHP_EOL;
