<?php

namespace BlueFission\Tests;

use BlueFission\Cli\Args;
use BlueFission\Cli\Args\OptionDefinition;
use BlueFission\Cli\ArgvTokenizer;
use PHPUnit\Framework\TestCase;

class ArgvTokenizerTest extends TestCase
{
    public function testPreservesQuotesEmptyArgsAndWindowsSeparators(): void
    {
        $line = <<<'COMMAND'
tool --name="two words" '' "" C:\Users\dev\file
COMMAND;
        $this->assertSame(
            ['tool', '--name=two words', '', '', 'C:\Users\dev\file'],
            ArgvTokenizer::tokenize($line, ['tool'])
        );
    }

    public function testEscapesQuotesAndSpaceWithoutInterpretingOtherBackslashes(): void
    {
        $line = <<<'COMMAND'
tool "say \"hi\"" 'it\'s' a\ b C:\tmp\é.txt
COMMAND;
        $this->assertSame(
            ['tool', 'say "hi"', "it's", 'a b', 'C:\tmp\é.txt'],
            ArgvTokenizer::tokenize($line, ['tool'])
        );
        $this->assertSame(['tool', 'C:\\dir\\'],
            ArgvTokenizer::tokenize("  tool\tC:\\dir\\", ['tool']));
    }

    public function testQuotedOperatorsRemainLiteralButUnquotedOperatorsFail(): void
    {
        $this->assertSame(['tool', ';|&><$()`'],
            ArgvTokenizer::tokenize("tool ';|&><$()`'", ['tool']));

        foreach (['tool ; next', 'tool | next', 'tool && next', 'tool > out',
            'tool < in', 'tool $(date)', 'tool `date`', 'tool %USER%', "tool\nnext"] as $line) {
            try {
                ArgvTokenizer::tokenize($line, ['tool']);
                $this->fail('Unquoted operator was accepted.');
            } catch (\InvalidArgumentException $exception) {
                $this->assertNotSame('', $exception->getMessage());
            }
        }
    }

    public function testRejectsUnclosedQuotesAndUnknownCommands(): void
    {
        foreach (['tool "unfinished', "tool 'unfinished", '', 'unknown --count=3'] as $line) {
            try {
                ArgvTokenizer::tokenize($line, ['tool']);
                $this->fail('Malformed or unknown command was accepted.');
            } catch (\InvalidArgumentException $exception) {
                $this->assertNotSame('', $exception->getMessage());
            }
        }
    }

    public function testReturnsArgvForExistingArgsParserWithoutDispatch(): void
    {
        $tokens = ArgvTokenizer::tokenize('tool --count=3 "file name.txt"', ['tool']);
        $this->assertSame(['tool', '--count=3', 'file name.txt'], $tokens);

        $args = new Args(['autoHelp' => false, 'allowUnknown' => false]);
        $args->addOption(new OptionDefinition('count', ['type' => 'int']));
        $args->parse($tokens);
        $this->assertSame(3, $args->options()['count']);
        $this->assertSame(['file name.txt'], $args->positionals());
    }
}
