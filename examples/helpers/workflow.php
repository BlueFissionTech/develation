<?php

declare(strict_types=1);

require __DIR__ . '/../support.php';

use BlueFission\Net\HTTP;
use BlueFission\Security\Hash;
use BlueFission\Str;

$fixtureDir = __DIR__ . DIRECTORY_SEPARATOR . 'fixtures';
$namesPath = $fixtureDir . DIRECTORY_SEPARATOR . 'names.txt';

$filesystem = filesystem($namesPath);
$names = arr($filesystem->lines("\n"))
    ->filter(fn (string $name): bool => str($name)->trim()->isNotEmpty())
    ->map(fn (string $name): string => str($name)->trim()->val())
    ->values();

$nameSummaries = collect($names->val())
    ->filter(fn (string $name, int $index): bool => $index < 2 || Str::contains($name, 'Johnson'))
    ->map(fn (string $name, int $index): array => [
        'index' => $index,
        'name' => $name,
        'slug' => str($name)->replace(' ', '-')->lower()->val(),
        'length' => str($name)->size(),
    ])
    ->toArray();

$angles = arr([0, 45, 90, 180])
    ->map(function (int $degrees): array {
        $radians = num($degrees)->deg2rad()->val();

        return [
            'degrees' => $degrees,
            'radians' => num($radians)->round(6)->val(),
            'sin' => num($radians)->sin()->round(6)->val(),
            'cos' => num($radians)->cos()->round(6)->val(),
        ];
    })
    ->values()
    ->val();

$directory = filesystem([
    'root' => $fixtureDir,
    'filter' => [],
    'doNotConfirm' => true,
]);

$marker = str('=')->repeat(3)->val();

$report = [
    'title' => $marker . ' DevElation helper workflow ' . $marker,
    'processed_on' => datetime()->val(),
    'source_file_exists' => doc()->exists($namesPath),
    'source_file_reachable' => doc()->isReachable($namesPath),
    'fixture_entries' => $directory->entries(),
    'name_count' => arr($names->val())->count(),
    'names_latest_first' => $names->reverse()->val(),
    'collection_name_summaries' => $nameSummaries,
    'admin_match' => str('Admin')->match('admin', Str::IGNORE_CASE),
    'enabled_flag' => flag('yes')->parseBool(),
    'status_line' => HTTP::statusLine(200),
    'encoded_path_segment' => HTTP::pathSegment('Example Report.md'),
    'url_host' => HTTP::urlHost('https://example.test:8443/docs?tab=api'),
    'content_id' => Hash::contentIdValue($names->val(), null, 'example'),
    'angles' => $angles,
];

echo HTTP::jsonEncode($report) . PHP_EOL;
