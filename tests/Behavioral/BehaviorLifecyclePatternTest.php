<?php

namespace BlueFission\Tests\Behavioral;

use BlueFission\Behavioral\Behaves;
use BlueFission\Behavioral\IDispatcher;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\Behavioral\Behaviors\State;
use PHPUnit\Framework\TestCase;

class BehaviorLifecyclePatternTest extends TestCase
{
    public function testStagedExecutionLifecycleRecordsOrderedMetadata(): void
    {
        $runner = new class implements IDispatcher {
            use Behaves;

            public function run(array $items, string $runId): void
            {
                $this->perform(State::INITIALIZING, new Meta(
                    when: Action::START,
                    info: 'Starting staged run',
                    data: ['run.id' => $runId, 'phase' => 'bootstrap']
                ));

                $this->perform(Event::INITIALIZED, new Meta(
                    data: ['run.id' => $runId, 'phase' => 'bootstrap', 'success' => true]
                ));

                $this->perform(State::PERFORMING_ACTION, new Meta(
                    when: Action::PROCESS,
                    data: ['run.id' => $runId, 'phase' => 'execute', 'input.count' => count($items)]
                ));

                $this->perform(Action::PROCESS, new Meta(
                    data: ['run.id' => $runId, 'phase' => 'execute', 'step' => 'process']
                ));

                $this->perform(State::PROCESSING, new Meta(
                    when: Action::PROCESS,
                    data: ['run.id' => $runId, 'phase' => 'execute']
                ));

                $this->perform(Event::PROCESSED, new Meta(
                    data: ['run.id' => $runId, 'phase' => 'execute', 'output.count' => count($items)]
                ));

                $this->perform(Event::COMPLETE, new Meta(
                    data: ['run.id' => $runId, 'phase' => 'complete', 'success' => true]
                ));
            }
        };

        $trace = [];
        $record = function ($behavior, $meta = null) use (&$trace): void {
            $data = $meta instanceof Meta ? $meta->data : [];

            $trace[] = [
                'behavior' => $behavior->name(),
                'run.id' => $data['run.id'] ?? null,
                'phase' => $data['phase'] ?? null,
                'success' => $data['success'] ?? null,
            ];
        };

        foreach ([State::INITIALIZING, Event::INITIALIZED, State::PERFORMING_ACTION, Action::PROCESS, Event::ACTION_PERFORMED, State::PROCESSING, Event::PROCESSED, Event::COMPLETE] as $behavior) {
            $runner->when($behavior, $record);
        }

        $runner->run(['first', 'second'], 'run-001');

        $this->assertSame([
            State::INITIALIZING,
            Event::INITIALIZED,
            State::PERFORMING_ACTION,
            Action::PROCESS,
            Event::ACTION_PERFORMED,
            State::PROCESSING,
            Event::PROCESSED,
            Event::COMPLETE,
        ], array_column($trace, 'behavior'));

        $this->assertSame('run-001', $trace[0]['run.id']);
        $this->assertSame('bootstrap', $trace[0]['phase']);
        $this->assertSame('complete', $trace[7]['phase']);
        $this->assertTrue($trace[7]['success']);
        $this->assertFalse($runner->is(State::INITIALIZING));
        $this->assertFalse($runner->is(State::PERFORMING_ACTION));
        $this->assertFalse($runner->is(State::PROCESSING));
    }
}
