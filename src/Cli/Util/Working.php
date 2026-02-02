<?php
namespace BlueFission\Cli\Util;

use BlueFission\Obj;
use BlueFission\Arr;
use BlueFission\Val;
use BlueFission\DataTypes;
use BlueFission\Async\Promise;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\DevElation as Dev;

class Working extends Obj
{
    protected Spinner $_spinner;

    protected $_data = [
        'label' => '',
        'frames' => ['|', '/', '-', '\\'],
        'intervalMs' => 120,
        'running' => false,
        'lastOutput' => '',
        'outputHandler' => null,
    ];

    protected $_types = [
        'label' => DataTypes::STRING,
        'frames' => DataTypes::ARRAY,
        'intervalMs' => DataTypes::INTEGER,
        'running' => DataTypes::BOOLEAN,
        'lastOutput' => DataTypes::STRING,
    ];

    public function __construct(string $label = '', ?array $frames = null, int $intervalMs = 120, ?callable $outputHandler = null)
    {
        parent::__construct();

        $label = Dev::apply('_in', $label);
        $frames = Dev::apply('_in', $frames);
        $intervalMs = Dev::apply('_in', $intervalMs);

        $this->setValue('label', (string)$label);
        $this->setValue('frames', Arr::is($frames) ? $frames : $this->_data['frames']);
        $this->setValue('intervalMs', max(10, (int)$intervalMs));
        $this->setValue('outputHandler', $outputHandler);
        $this->setValue('running', false);

        $this->_spinner = new Spinner($label, $this->getValue('frames'), $this->getValue('intervalMs'));

        $this->behavior(new Action(Action::START), function () {
            $this->setValue('running', true);
            $this->_spinner->start();
            $this->trigger(Event::STARTED);
        });

        $this->behavior(new Action(Action::STOP), function () {
            $this->setValue('running', false);
            $this->_spinner->stop();
            $this->trigger(Event::STOPPED);
        });

        $this->behavior(new Action(Action::UPDATE), function ($behavior, $args) {
            $meta = ($args instanceof Meta) ? $args : null;
            if ($meta && is_array($meta->data ?? null)) {
                $data = $meta->data;
                if (array_key_exists('label', $data)) {
                    $this->setValue('label', (string)$data['label']);
                    $this->_spinner->label((string)$data['label']);
                }
                if (array_key_exists('frames', $data) && Arr::is($data['frames'])) {
                    $this->setValue('frames', $data['frames']);
                    $this->_spinner->frames($data['frames']);
                }
                if (array_key_exists('intervalMs', $data)) {
                    $interval = max(10, (int)$data['intervalMs']);
                    $this->setValue('intervalMs', $interval);
                    $this->_spinner->interval($interval);
                }
                if (array_key_exists('outputHandler', $data)) {
                    $this->setValue('outputHandler', $data['outputHandler']);
                }
            }
            $this->trigger(Event::CHANGE, $meta);
        });
    }

    public function outputHandler(?callable $handler = null)
    {
        if (Val::isNull($handler)) {
            return $this->getValue('outputHandler');
        }

        $this->setValue('outputHandler', $handler);
        return $this;
    }

    public function run(callable $work)
    {
        $work = Dev::apply('_in', $work);
        Dev::do('_before', [$work, $this]);
        $this->perform(new Action(Action::START));

        try {
            $result = $this->invoke($work);

            if ($result instanceof Promise) {
                $result = $this->runPromise($result);
            } elseif ($result instanceof \Generator) {
                $result = $this->runGenerator($result);
            }
        } finally {
            $this->perform(new Action(Action::STOP));
        }

        $result = Dev::apply('_out', $result);
        $this->trigger(Event::PROCESSED, new Meta(data: $result));
        Dev::do('_after', [$result, $this]);
        return $result;
    }

    public function tick(?float $nowMs = null): string
    {
        $output = $this->_spinner->tick($nowMs);
        $this->write($output);
        return $output;
    }

    protected function runPromise(Promise $promise)
    {
        $done = false;
        $result = null;
        $error = null;

        $promise->then(
            function ($value = null) use (&$done, &$result) {
                $result = $value;
                $done = true;
            },
            function ($reason = null) use (&$done, &$error) {
                $error = $reason;
                $done = true;
            }
        );

        $promise->try();

        while (!$done) {
            $this->tick();
            usleep((int)$this->getValue('intervalMs') * 1000);
        }

        if ($error instanceof \Throwable) {
            throw $error;
        }

        return $result;
    }

    protected function runGenerator(\Generator $generator)
    {
        $last = null;
        foreach ($generator as $value) {
            $last = $value;
            $this->tick();
            usleep((int)$this->getValue('intervalMs') * 1000);
        }

        try {
            $returnValue = $generator->getReturn();
            if (Val::isNotNull($returnValue)) {
                return $returnValue;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return $last;
    }

    protected function write(string $text): void
    {
        $handler = $this->getValue('outputHandler');
        $payload = Screen::rewriteLine($text);
        $this->setValue('lastOutput', $payload);

        if (is_callable($handler)) {
            call_user_func($handler, $payload);
        } else {
            echo $payload;
        }
    }

    protected function invoke(callable $work)
    {
        try {
            $reflection = new \ReflectionFunction(\Closure::fromCallable($work));
            if ($reflection->getNumberOfParameters() > 0) {
                return $work($this);
            }
        } catch (\Throwable $e) {
            // fallback below
        }

        return $work();
    }

    protected function setValue(string $field, $value): void
    {
        $current = $this->_data[$field] ?? null;
        if ($current instanceof \BlueFission\IVal) {
            $current->val($value);
            return;
        }
        $this->_data[$field] = $value;
    }

    protected function getValue(string $field)
    {
        $current = $this->_data[$field] ?? null;
        if ($current instanceof \BlueFission\IVal) {
            return $current->val();
        }
        return $current;
    }
}
