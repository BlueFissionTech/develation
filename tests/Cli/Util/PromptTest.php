<?php
namespace BlueFission\Tests;

use BlueFission\Cli\Util\Prompt;
use BlueFission\Behavioral\Behaviors\Event;

class PromptTest extends \PHPUnit\Framework\TestCase
{
    public function testAskUsesDefaultOnEmptyInput()
    {
        $this->assertSame('default', Prompt::ask('', 'default', ''));
    }

    public function testConfirmParsesInput()
    {
        $this->assertFalse(Prompt::confirm('', true, 'n'));
        $this->assertTrue(Prompt::confirm('', false, 'yes'));
        $this->assertTrue(Prompt::confirm('', false, '  YES  '));
    }

    public function testChoiceResolvesSelection()
    {
        $choices = [
            'a' => 'apple',
            'b' => 'banana',
            'c' => 'cherry',
        ];

        $this->assertSame('banana', Prompt::choice('', $choices, null, 'b'));
        $this->assertSame('cherry', Prompt::choice('', $choices, null, 'Cherry'));
        $this->assertSame('cherry', Prompt::choice('', $choices, null, '  CHERRY  '));
        $this->assertSame('apple', Prompt::choice('', $choices, 'apple', ''));
    }

    public function testPromptFiresReceivedEvent()
    {
        $prompt = new Prompt();
        $received = false;

        $prompt->when(new Event(Event::RECEIVED), function () use (&$received) {
            $received = true;
        });

        $prompt->askPrompt('Name?', null, 'Ada');

        $this->assertTrue($received);
    }

    public function testSensitiveReaderReturnsValueWithoutPromptStateEventsOrOutput(): void
    {
        $prompt = new Prompt();
        $events = 0;
        $prompt->when(new Event(Event::RECEIVED), function () use (&$events): void {
            $events++;
        });
        $prompt->when(new Event(Event::PROCESSED), function () use (&$events): void {
            $events++;
        });
        $prompt->askPrompt('Ordinary?', null, 'retained');
        $baselineEvents = $events;
        $this->assertSame('retained', $prompt->field('lastResponse'));

        ob_start();
        try {
            $answer = Prompt::askSensitive('Secret?', function (string $message): string {
                $this->assertSame('Secret?', $message);
                return '  secret bytes  ';
            });
        } finally {
            $output = ob_get_clean();
        }

        $this->assertSame('  secret bytes  ', $answer);
        $this->assertSame('', $output);
        $this->assertSame('retained', $prompt->field('lastResponse'));
        $this->assertSame($baselineEvents, $events);
    }

    public function testSensitiveReaderDistinguishesEmptyDefaultFromEof(): void
    {
        $this->assertSame('default', Prompt::askSensitive('', fn (): string => '', 'default'));
        $this->assertSame('  ', Prompt::askSensitive('', fn (): string => '  ', 'default'));
        $this->assertSame('default', Prompt::askSensitive('', fn (): string => '  ', 'default', true));
        $this->assertNull(Prompt::askSensitive('', fn (): ?string => null, 'default'));
        $this->assertNull(Prompt::askSensitive('', fn (): bool => false, 'default'));
    }

    public function testSensitiveReaderPropagatesExceptionsWithoutFallback(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('reader cancelled');
        Prompt::askSensitive('', function (): never {
            throw new \RuntimeException('reader cancelled');
        }, 'default');
    }

    public function testSensitiveReaderRejectsUnsupportedResultsAndHasNoHistory(): void
    {
        $this->assertSame('first', Prompt::askSensitive('', fn (): string => 'first'));
        $this->assertSame('second', Prompt::askSensitive('', fn (): string => 'second'));
        $this->expectException(\UnexpectedValueException::class);
        Prompt::askSensitive('', fn (): int => 123);
    }
}
