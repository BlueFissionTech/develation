<?php
namespace BlueFission\Tests;

use BlueFission\Cli\Args;
use BlueFission\Cli\Args\OptionDefinition;
use BlueFission\Behavioral\Behaviors\Event;

class ArgsTest extends \PHPUnit\Framework\TestCase
{
    public function testParsesOptionsAndPositionals()
    {
        $parser = new Args();
        $parser->addOption(new OptionDefinition('count', [
            'short' => 'c',
            'type' => 'int',
            'default' => 1,
        ]));
        $parser->addOption(new OptionDefinition('verbose', [
            'short' => 'v',
            'type' => 'bool',
        ]));

        $parser->parse(['tool.php', '--count=3', '-v', 'file.txt']);

        $this->assertSame(3, $parser->options()['count']);
        $this->assertTrue($parser->options()['verbose']);
        $this->assertSame(['file.txt'], $parser->positionals());
    }

    public function testRepeatableArrayOptions()
    {
        $parser = new Args(['autoHelp' => false]);
        $parser->addOption(new OptionDefinition('tag', [
            'type' => 'array',
            'repeatable' => true,
        ]));

        $parser->parse(['tool.php', '--tag', 'one', '--tag', 'two,three']);

        $this->assertSame(['one', 'two', 'three'], $parser->options()['tag']);
    }

    public function testGroupedShortBooleanOptions()
    {
        $parser = new Args(['autoHelp' => false]);
        $parser->addOption(new OptionDefinition('verbose', [
            'short' => 'v',
            'type' => 'bool',
        ]));
        $parser->addOption(new OptionDefinition('force', [
            'short' => 'f',
            'type' => 'bool',
        ]));

        $parser->parse(['tool.php', '-vf']);

        $this->assertTrue($parser->options()['verbose']);
        $this->assertTrue($parser->options()['force']);
    }

    public function testEnvFallbackAndNoFlag()
    {
        putenv('TEST_ARGS_MODE=quiet');
        putenv('TEST_ARGS_DEBUG=off');

        $parser = new Args(['autoHelp' => false]);
        $parser->addOption(new OptionDefinition('mode', [
            'type' => 'string',
            'env' => 'TEST_ARGS_MODE',
        ]));
        $parser->addOption(new OptionDefinition('debug', [
            'type' => 'bool',
            'env' => 'TEST_ARGS_DEBUG',
        ]));
        $parser->addOption(new OptionDefinition('color', [
            'type' => 'bool',
        ]));

        $parser->parse(['tool.php', '--no-color']);

        $this->assertSame('quiet', $parser->options()['mode']);
        $this->assertFalse($parser->options()['debug']);
        $this->assertFalse($parser->options()['color']);

        putenv('TEST_ARGS_MODE');
        putenv('TEST_ARGS_DEBUG');
    }

    public function testUnknownArgsCollected()
    {
        $parser = new Args(['autoHelp' => false]);
        $parser->parse(['tool.php', '--unknown']);

        $this->assertSame(['--unknown'], $parser->unknown());
    }

    public function testDoubleDashKeepsRemainingValuesAsPositionals()
    {
        $parser = new Args(['autoHelp' => false]);
        $parser->addOption(new OptionDefinition('verbose', [
            'short' => 'v',
            'type' => 'bool',
        ]));

        $parser->parse(['tool.php', '-v', '--', '--literal', 'file.txt']);

        $this->assertTrue($parser->options()['verbose']);
        $this->assertSame(['--literal', 'file.txt'], $parser->positionals());
    }

    public function testStrictBooleansRejectInvalidInputsBeforeDispatch()
    {
        $parser = new Args(['autoHelp' => false, 'strictBooleans' => true]);
        $parser->addOption(new OptionDefinition('apply', [
            'short' => 'a',
            'aliases' => ['commit'],
            'type' => 'bool',
        ]));

        $writes = 0;
        $processed = 0;
        $parser->when(new Event(Event::PROCESSED), function () use (&$processed): void {
            $processed++;
        });
        $dispatch = function (array $argv) use ($parser, &$writes): void {
            $parser->parse($argv);
            if ($parser->options()['apply'] ?? false) {
                $writes++;
            }
        };

        foreach ([
            ['tool', '--apply=garbage'],
            ['tool', '--apply='],
            ['tool', '--apply', 'garbage'],
            ['tool', '--no-apply=true'],
            ['tool', '--apply', '--commit'],
            ['tool', '--apply', '--apply'],
            ['tool', '-a', '--no-apply'],
            ['tool', '--no-apply', 'false'],
        ] as $argv) {
            try {
                $dispatch($argv);
                $this->fail('Strict parsing accepted ' . implode(' ', $argv));
            } catch (\InvalidArgumentException $exception) {
                $this->assertStringContainsString('apply', $exception->getMessage());
                $this->assertSame([], $parser->options());
            }
        }

        $this->assertSame(0, $writes);
        $this->assertSame(0, $processed);
        $dispatch(['tool', '--apply=false']);
        $this->assertSame(0, $writes);
        $dispatch(['tool', '--apply=true']);
        $this->assertSame(1, $writes);
        $this->assertSame(2, $processed);
    }

    public function testStrictBooleansAcceptExplicitValuesAndDelimitedPositionals()
    {
        $parser = new Args(['autoHelp' => false, 'strictBooleans' => true]);
        $parser->addOption(new OptionDefinition('apply', [
            'short' => 'a',
            'aliases' => ['commit'],
            'type' => 'bool',
        ]));

        foreach ([
            [['tool', '--apply', 'false'], false, []],
            [['tool', '--commit=yes'], true, []],
            [['tool', '--no-apply'], false, []],
            [['tool', '-a', 'off'], false, []],
            [['tool', '--apply', '--', 'file'], true, ['file']],
        ] as [$argv, $expected, $positionals]) {
            $parser->parse($argv);
            $this->assertSame($expected, $parser->options()['apply']);
            $this->assertSame($positionals, $parser->positionals());
        }
    }

    public function testStrictBooleansValidateEnvironmentAndDefaults()
    {
        putenv('TEST_ARGS_STRICT_APPLY=garbage');
        try {
            $parser = new Args(['autoHelp' => false, 'strictBooleans' => true]);
            $parser->addOption(new OptionDefinition('apply', [
                'type' => 'bool', 'env' => 'TEST_ARGS_STRICT_APPLY',
            ]));
            try {
                $parser->parse(['tool']);
                $this->fail('Invalid environment value was accepted.');
            } catch (\InvalidArgumentException $exception) {
                $this->assertStringContainsString('apply', $exception->getMessage());
            }
        } finally {
            putenv('TEST_ARGS_STRICT_APPLY');
        }

        $parser = new Args(['autoHelp' => false, 'strictBooleans' => true]);
        $parser->addOption(new OptionDefinition('apply', [
            'type' => 'bool', 'default' => 'garbage',
        ]));
        $this->expectException(\InvalidArgumentException::class);
        $parser->parse(['tool']);
    }

    public function testStrictBooleanHelpAndLegacyModeRemainAvailable()
    {
        $strict = new Args(['strictBooleans' => true]);
        $strict->parse(['tool', '--help']);
        $this->assertTrue($strict->options()['help']);
        $this->assertStringContainsString('--help', $strict->usage());

        $legacy = new Args(['autoHelp' => false]);
        $legacy->addOption(new OptionDefinition('apply', ['type' => 'bool']));
        $legacy->parse(['tool', '--apply=garbage']);
        $this->assertTrue($legacy->options()['apply']);
    }

    public function testStrictBooleanValidEnvironmentAndDefaultTokens()
    {
        putenv('TEST_ARGS_STRICT_APPLY=off');
        try {
            $parser = new Args(['autoHelp' => false, 'strictBooleans' => true]);
            $parser->addOption(new OptionDefinition('apply', [
                'type' => 'bool', 'env' => 'TEST_ARGS_STRICT_APPLY',
            ]));
            $parser->parse(['tool']);
            $this->assertFalse($parser->options()['apply']);
        } finally {
            putenv('TEST_ARGS_STRICT_APPLY');
        }

        $parser = new Args(['autoHelp' => false, 'strictBooleans' => true]);
        $parser->addOption(new OptionDefinition('apply', [
            'type' => 'bool', 'default' => 'yes',
        ]));
        $parser->parse(['tool']);
        $this->assertTrue($parser->options()['apply']);
    }
}
