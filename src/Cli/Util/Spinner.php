<?php
namespace BlueFission\Cli\Util;

use BlueFission\Obj;
use BlueFission\Arr;
use BlueFission\Val;
use BlueFission\DataTypes;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\DevElation as Dev;

class Spinner extends Obj
{
    protected $_data = [
        'label' => '',
        'frames' => ['|', '/', '-', '\\'],
        'index' => 0,
        'intervalMs' => 120,
        'running' => false,
        'lastTick' => 0.0,
    ];

    protected $_types = [
        'label' => DataTypes::STRING,
        'frames' => DataTypes::ARRAY,
        'index' => DataTypes::INTEGER,
        'intervalMs' => DataTypes::INTEGER,
        'running' => DataTypes::BOOLEAN,
        'lastTick' => DataTypes::NUMBER,
    ];

    public function __construct(string $label = '', ?array $frames = null, int $intervalMs = 120)
    {
        parent::__construct();

        $label = Dev::apply('_in', $label);
        $frames = Dev::apply('_in', $frames);
        $intervalMs = Dev::apply('_in', $intervalMs);

        $this->setValue('label', (string)$label);
        $this->setValue('frames', Arr::is($frames) ? $frames : $this->_data['frames']);
        $this->setValue('intervalMs', max(10, (int)$intervalMs));
        $this->setValue('index', 0);
        $this->setValue('running', false);
        $this->setValue('lastTick', 0.0);

        $this->behavior(new Action(Action::START), function () {
            $this->setValue('running', true);
            $this->setValue('lastTick', $this->timestampMs());
            $this->trigger(Event::STARTED);
        });

        $this->behavior(new Action(Action::STOP), function () {
            $this->setValue('running', false);
            $this->trigger(Event::STOPPED);
        });

        $this->behavior(new Action(Action::UPDATE), function ($behavior, $args) {
            $meta = ($args instanceof Meta) ? $args : null;
            if ($meta && is_array($meta->data ?? null)) {
                $data = $meta->data;
                if (array_key_exists('label', $data)) {
                    $this->setValue('label', (string)$data['label']);
                }
                if (array_key_exists('frames', $data) && Arr::is($data['frames'])) {
                    $this->setValue('frames', $data['frames']);
                    $this->setValue('index', 0);
                }
                if (array_key_exists('intervalMs', $data)) {
                    $this->setValue('intervalMs', max(10, (int)$data['intervalMs']));
                }
            }
            $this->trigger(Event::CHANGE, $meta);
        });

        $this->behavior(new Action(Action::PROCESS), function () {
            $output = $this->render();
            $this->trigger(Event::PROCESSED, new Meta(data: $output));
        });
    }

    public function start(): self
    {
        $this->perform(new Action(Action::START));
        return $this;
    }

    public function stop(): self
    {
        $this->perform(new Action(Action::STOP));
        return $this;
    }

    public function label(string $label): self
    {
        $this->perform(new Action(Action::UPDATE), new Meta(data: ['label' => $label]));
        return $this;
    }

    public function frames(array $frames): self
    {
        $this->perform(new Action(Action::UPDATE), new Meta(data: ['frames' => $frames]));
        return $this;
    }

    public function interval(int $intervalMs): self
    {
        $this->perform(new Action(Action::UPDATE), new Meta(data: ['intervalMs' => $intervalMs]));
        return $this;
    }

    public function tick(?float $nowMs = null): string
    {
        $nowMs = Dev::apply('_in', $nowMs);
        if (!$this->getValue('running')) {
            $this->start();
        }

        $now = Val::isNotNull($nowMs) ? (float)$nowMs : $this->timestampMs();
        $lastTick = (float)$this->getValue('lastTick');
        $interval = (int)$this->getValue('intervalMs');

        if ($lastTick <= 0 || ($now - $lastTick) >= $interval) {
            $this->advance();
            $this->setValue('lastTick', $now);
        }

        $output = $this->render();
        $output = Dev::apply('_out', $output);
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function render(): string
    {
        Dev::do('_before', [$this]);
        $label = (string)$this->getValue('label');
        $frame = $this->frame();

        $output = trim($label . ' ' . $frame);
        $output = Dev::apply('_out', $output);
        $this->trigger(Event::PROCESSED, new Meta(data: $output));
        Dev::do('_after', [$output, $this]);
        return $output;
    }

    public function frame(): string
    {
        $frames = $this->getValue('frames');
        if (!Arr::is($frames) || count($frames) === 0) {
            return '';
        }

        $index = (int)$this->getValue('index');
        $frame = $frames[$index % count($frames)] ?? '';
        return (string)$frame;
    }

    public function advance(): self
    {
        $frames = $this->getValue('frames');
        $count = Arr::is($frames) ? count($frames) : 0;
        $index = (int)$this->getValue('index');
        $index = $count > 0 ? ($index + 1) % $count : 0;
        $this->setValue('index', $index);
        $this->trigger(Event::CHANGE);
        return $this;
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

    protected function timestampMs(): float
    {
        return microtime(true) * 1000;
    }
}
