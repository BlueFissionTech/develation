<?php
namespace BlueFission\Cli\Util;

use BlueFission\Obj;
use BlueFission\DataTypes;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\DevElation as Dev;

class Cursor extends Obj
{
    protected $_data = [
        'x' => 1,
        'y' => 1,
        'visible' => true,
    ];

    protected $_types = [
        'x' => DataTypes::INTEGER,
        'y' => DataTypes::INTEGER,
        'visible' => DataTypes::BOOLEAN,
    ];

    public function __construct(int $x = 1, int $y = 1, bool $visible = true)
    {
        parent::__construct();

        $x = Dev::apply('_in', $x);
        $y = Dev::apply('_in', $y);
        $visible = Dev::apply('_in', $visible);

        $this->setValue('x', max(1, $x));
        $this->setValue('y', max(1, $y));
        $this->setValue('visible', $visible);

        $this->behavior(new Action(Action::UPDATE), function ($behavior, $args) {
            $meta = ($args instanceof Meta) ? $args : null;
            if ($meta && is_array($meta->data ?? null)) {
                $data = $meta->data;
                if (array_key_exists('x', $data)) {
                    $this->setValue('x', max(1, (int)$data['x']));
                }
                if (array_key_exists('y', $data)) {
                    $this->setValue('y', max(1, (int)$data['y']));
                }
                if (array_key_exists('visible', $data)) {
                    $this->setValue('visible', (bool)$data['visible']);
                }
            }
            $this->trigger(Event::CHANGE, $meta);
        });

        $this->behavior(new Action(Action::SEND), function ($behavior, $args) {
            $this->trigger(Event::SENT, $args instanceof Meta ? $args : null);
        });
    }

    public function moveTo(int $x, int $y): self
    {
        Dev::do('_before', [$this, $x, $y]);
        $this->perform(new Action(Action::UPDATE), new Meta(data: ['x' => $x, 'y' => $y]));
        Dev::do('_after', [$this]);
        return $this;
    }

    public function show(): self
    {
        $this->perform(new Action(Action::UPDATE), new Meta(data: ['visible' => true]));
        return $this;
    }

    public function hide(): self
    {
        $this->perform(new Action(Action::UPDATE), new Meta(data: ['visible' => false]));
        return $this;
    }

    public function renderPosition(): string
    {
        Dev::do('_before', [$this]);
        $output = Screen::moveCursor((int)$this->getValue('x'), (int)$this->getValue('y'));
        $output = Dev::apply('_out', $output);
        $this->perform(new Action(Action::SEND), new Meta(data: $output));
        $this->trigger(Event::PROCESSED, new Meta(data: $output));
        Dev::do('_after', [$output, $this]);

        return $output;
    }

    public function renderVisibility(): string
    {
        Dev::do('_before', [$this]);
        $output = $this->getValue('visible') ? Screen::showCursor() : Screen::hideCursor();
        $output = Dev::apply('_out', $output);
        $this->perform(new Action(Action::SEND), new Meta(data: $output));
        $this->trigger(Event::PROCESSED, new Meta(data: $output));
        Dev::do('_after', [$output, $this]);

        return $output;
    }

    public function x(): int
    {
        return (int)$this->getValue('x');
    }

    public function y(): int
    {
        return (int)$this->getValue('y');
    }

    public function visible(): bool
    {
        return (bool)$this->getValue('visible');
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
