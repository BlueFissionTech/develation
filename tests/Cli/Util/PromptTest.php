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
}
