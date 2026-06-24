<?php

declare(strict_types=1);

require __DIR__ . '/../support.php';

use BlueFission\Arr;
use BlueFission\Cli\Args;
use BlueFission\Cli\Args\OptionDefinition;
use BlueFission\Cli\Console;
use BlueFission\Cli\Util\StatusBar;
use BlueFission\Date;
use BlueFission\Num;
use BlueFission\Str;

$args = (new Args())
    ->addOptions([
        new OptionDefinition('limit', [
            'short' => 'l',
            'type' => 'int',
            'default' => 5,
            'description' => 'Number of rows to render.',
        ]),
        new OptionDefinition('delay', [
            'short' => 'd',
            'type' => 'int',
            'default' => 0,
            'description' => 'Delay per step (ms).',
        ]),
        new OptionDefinition('title', [
            'short' => 't',
            'type' => 'string',
            'default' => 'CLI Report',
            'description' => 'Title for the output.',
        ]),
        new OptionDefinition('ask', [
            'short' => 'a',
            'type' => 'bool',
            'default' => false,
            'description' => 'Prompt for a custom title.',
        ]),
    ])
    ->parse($argv);

$options = $args->options();

$console = new Console();

if (!empty($options['help'])) {
    $console->writeln($args->usage());
    exit(0);
}

$limit = (int)Num::max((int)($options['limit'] ?? 5), 1);
$delay = (int)Num::max((int)($options['delay'] ?? 0), 0);
$title = Str::trim((string)($options['title'] ?? 'CLI Report'));

if (!empty($options['ask'])) {
    $title = Str::trim($console->prompt('Report title: ', $title));
}

$console->writeln($console->color($title, 'cyan', ['bold']));

$rows = Arr::make(range(1, $limit))
    ->map(fn (int $i): array => ['Item ' . $i, 'ready'])
    ->values()
    ->val();

$console->writeln($console->table(['Item', 'Status'], $rows, [
    'align' => ['left', 'right'],
]));

$status = new StatusBar();
$status
    ->set('total', (string)Arr::count($rows))
    ->set('mode', 'demo')
    ->set('date', Date::formatTimestamp(time()));
$console->writeln($status->render());

for ($i = 1; $i <= $limit; $i++) {
    $console->rewriteLine($console->progress($limit, $i));
    if ($delay > 0) {
        usleep($delay * 1000);
    }
}
$console->writeln('');
