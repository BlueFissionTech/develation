<?php
namespace BlueFission\Tests;

use BlueFission\Cli\Args;
use BlueFission\Cli\Args\OptionDefinition;

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
}
