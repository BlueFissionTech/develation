<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use BlueFission\Behavioral\Behaves;
use BlueFission\Behavioral\Behaviors\State;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Str;
use BlueFission\Arr;
use BlueFission\Data\Log;

// Simple NPC that uses DevElation's behavioral state machine to model territory changes.
// This is intentionally small but shows how states like PROCESSING/CREATING/DELETING wrap logic.
class GangNpc implements \BlueFission\Behavioral\IDispatcher
{
    // Behaves pulls in Dispatches/behavior engine; we alias its constructor
    // so we can initialize behavioral state before our own setup.
    use Behaves {
        Behaves::__construct as private __behavesConstruct;
    }

    protected array $_config = [];

    private string $name;
    private int $territory = 1;

    public function __construct(string $name)
    {
        // Initialize the Behaves/Dispatches engine.
        $this->__behavesConstruct();
        $this->name = $name;

        // Configure basic behaviors for the NPC so states can be performed/halted.
        $this->behavior(State::PROCESSING);
        $this->behavior(State::CREATING);
        $this->behavior(State::DELETING);
        $this->behavior(State::IDLE);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function territory(): int
    {
        return $this->territory;
    }

    public function tick(): string
    {
        $this->perform(State::PROCESSING);

        $roll = random_int(1, 3);

        if ($roll === 1) {
            $this->perform(State::CREATING);
            $this->territory++;
            $this->halt(State::CREATING);
            $this->halt(State::PROCESSING);
            return "{$this->name} quietly expands territory.";
        }

        if ($roll === 2 && $this->territory > 1) {
            $this->perform(State::DELETING);
            $this->territory--;
            $this->halt(State::DELETING);
            $this->halt(State::PROCESSING);
            return "{$this->name} loses some ground.";
        }

        $this->perform(State::IDLE);
        $this->halt(State::PROCESSING);
        return "{$this->name} is waiting for a move.";
    }
}

// Game loop that can run interactively or in a naive "scripted" single-player mode.
// The dependencies (GangNpc) are simple objects; in a larger app this would accept
// injected services or datasources so you can swap DB/file/mock backends.
class GangGame
{
    private GangNpc $player;
    private GangNpc $rival;
    private Arr $log;

    public function __construct()
    {
        $this->player = new GangNpc('Player');
        $this->rival = new GangNpc('Rival');
        $this->log = new Arr([]);
    }

    /**
     * Interactive loop: read from STDIN and step the game using DevElation behaviors.
     */
    public function run(): void
    {
        $this->line('Welcome to DevElation Gangs.');
        $this->line('Type "attack", "wait", or "quit".');

        while (true) {
            $this->renderState();

            $this->prompt('> ');
            $input = trim((string)fgets(STDIN));

            if (!$this->step($input)) {
                break;
            }
        }

        $this->printRecap();
    }

    /**
     * Naive single-player scripted mode: feed a fixed sequence of actions
     * instead of waiting for user input. Useful for demos, tests, or CI.
     *
     * @param array<string> $actions
     */
    public function runScripted(array $actions = null): void
    {
        $this->line('Scripted DevElation Gangs run.');
        $actions = $actions ?? ['attack', 'attack', 'wait', 'attack', 'wait', 'attack'];

        foreach ($actions as $action) {
            $this->renderState();
            $this->line('> ' . $action);

            if (!$this->step($action)) {
                break;
            }
        }

        $this->printRecap();
    }

    /**
     * Apply one player action + NPC response. Returns false when the game ends.
     */
    private function step(string $input): bool
    {
        if ($input === 'quit') {
            $this->line('Goodbye.');
            return false;
        }

        if ($input === 'attack') {
            $this->playerAttack();
        } elseif ($input === 'wait') {
            $this->line('You hold your ground.');
        } else {
            $this->line('Unknown command.');
            return true;
        }

        $npcNarration = $this->rival->tick();
        $this->log->push($npcNarration);
        $this->line($npcNarration);

        if ($this->player->territory() <= 0) {
            $this->line('You have lost all territory. Game over.');
            return false;
        }

        if ($this->rival->territory() <= 0) {
            $this->line('The rival has no remaining territory. You win.');
            return false;
        }

        return true;
    }

    private function playerAttack(): void
    {
        $this->line('You make a move on rival territory.');
        $roll = random_int(1, 2);

        if ($roll === 1) {
            $this->line('Success. You gain ground.');
            $this->shiftTerritory($this->player, $this->rival);
        } else {
            $this->line('The move fails and you lose ground.');
            $this->shiftTerritory($this->rival, $this->player);
        }
    }

    private function shiftTerritory(GangNpc $winner, GangNpc $loser): void
    {
        $winnerReflection = new ReflectionClass($winner);
        $winnerProperty = $winnerReflection->getProperty('territory');
        $winnerProperty->setAccessible(true);
        $winnerProperty->setValue($winner, $winner->territory() + 1);

        $loserReflection = new ReflectionClass($loser);
        $loserProperty = $loserReflection->getProperty('territory');
        $loserProperty->setAccessible(true);
        $loserProperty->setValue($loser, max(0, $loser->territory() - 1));
    }

    private function renderState(): void
    {
        $this->line('');
        $this->line('Territory:');
        $this->line('  You   : ' . $this->player->territory());
        $this->line('  Rival : ' . $this->rival->territory());
    }

    /**
     * Print a recap of NPC actions using Arr as a simple value object.
     */
    private function printRecap(): void
    {
        $logger = Log::instance();
        $logger->config(['instant' => true]);

        $this->line('');
        $this->line('Recap of NPC actions:');
        foreach ($this->log->val() as $entry) {
            $this->line('- ' . $entry);
            $logger->push("gangs.npc: {$entry}");
        }
    }

    private function line(string $message): void
    {
        fwrite(STDOUT, $message . PHP_EOL);
    }

    private function prompt(string $message): void
    {
        fwrite(STDOUT, $message);
    }
}

// Entry point: default to interactive mode, but allow a simple scripted
// run when called with `php gangs.php script`.
$game = new GangGame();
global $argv;
$mode = $argv[1] ?? null;

if ($mode === 'script') {
    $game->runScripted();
} else {
    $game->run();
}
