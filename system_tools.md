# System Tools

System tools cover CLI interactions, OS-level process control, machine inspection, and async helpers. Most components are behavior-aware so you can listen for state changes or events.

## Key Areas

- `Cli`: terminal utilities (Console, Args, Ansi, Table, ProgressBar, Prompt, Cursor).
- `System`: process control and machine details (`Process`, `System`, `Machine`).
- `Async`: promises, forks, sockets, and basic async helpers.
- `IPC`: lightweight inter-process communication helper.

## Quick Start: CLI Output

```php
use BlueFission\Cli\Console;

$console = new Console();
$console->writeln($console->color('Starting up', 'green'));
$console->writeln($console->table(['Key', 'Value'], [
    ['mode', 'cli'],
    ['status', 'ready'],
]));
```

## Quick Start: Process Execution

```php
use BlueFission\System\Process;

$process = new Process('php -v');
$process->start();
$output = $process->output();
$process->stop();

echo $output;
```

## Related

CLI utilities are documented in `src/Cli` and integrate with `Behavioral` events.
Async helpers live in `src/Async`.
