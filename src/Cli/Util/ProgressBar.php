<?php
namespace BlueFission\Cli\Util;

use BlueFission\Obj;
use BlueFission\Val;
use BlueFission\DataTypes;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\DevElation as Dev;

class ProgressBar extends Obj
{
    protected $_data = [
        'total' => 0,
        'current' => 0,
        'width' => 40,
        'fillChar' => '#',
        'emptyChar' => '-',
        'showPercent' => true,
        'showCount' => true,
    ];

    protected $_types = [
        'total' => DataTypes::INTEGER,
        'current' => DataTypes::INTEGER,
        'width' => DataTypes::INTEGER,
        'fillChar' => DataTypes::STRING,
        'emptyChar' => DataTypes::STRING,
        'showPercent' => DataTypes::BOOLEAN,
        'showCount' => DataTypes::BOOLEAN,
    ];

    public function __construct(int $total, int $width = 40, string $fillChar = '#', string $emptyChar = '-')
    {
        parent::__construct();

        $total = Dev::apply('_in', $total);
        $width = Dev::apply('_in', $width);
        $fillChar = Dev::apply('_in', $fillChar);
        $emptyChar = Dev::apply('_in', $emptyChar);

        $this->setValue('total', max(0, $total));
        $this->setValue('current', 0);
        $this->setValue('width', max(1, $width));
        $this->setValue('fillChar', $fillChar);
        $this->setValue('emptyChar', $emptyChar);

        $this->behavior(new Action(Action::UPDATE), function ($behavior, $args) {
            $meta = ($args instanceof Meta) ? $args : null;
            if ($meta && is_array($meta->data ?? null)) {
                $data = $meta->data;
                if (array_key_exists('total', $data)) {
                    $this->setValue('total', max(0, (int)$data['total']));
                }
                if (array_key_exists('current', $data)) {
                    $this->setValue('current', max(0, (int)$data['current']));
                }
                if (array_key_exists('width', $data)) {
                    $this->setValue('width', max(1, (int)$data['width']));
                }
                if (array_key_exists('showPercent', $data)) {
                    $this->setValue('showPercent', (bool)$data['showPercent']);
                }
                if (array_key_exists('showCount', $data)) {
                    $this->setValue('showCount', (bool)$data['showCount']);
                }
            }
            $this->trigger(Event::CHANGE, $meta);
        });

        $this->behavior(new Action(Action::PROCESS), function ($behavior, $args) {
            $output = $this->render();
            $this->trigger(Event::PROCESSED, new Meta(data: $output));
        });
    }

    public function setTotal(int $total): self
    {
        $this->perform(new Action(Action::UPDATE), new Meta(data: ['total' => $total]));
        return $this;
    }

    public function setCurrent(int $current): self
    {
        $this->perform(new Action(Action::UPDATE), new Meta(data: ['current' => $current]));
        return $this;
    }

    public function advance(int $step = 1): self
    {
        $this->perform(new Action(Action::UPDATE), new Meta(data: ['current' => $this->getValue('current') + $step]));
        return $this;
    }

    public function showPercent(bool $show): self
    {
        $this->perform(new Action(Action::UPDATE), new Meta(data: ['showPercent' => $show]));
        return $this;
    }

    public function showCount(bool $show): self
    {
        $this->perform(new Action(Action::UPDATE), new Meta(data: ['showCount' => $show]));
        return $this;
    }

    public function render(?int $current = null): string
    {
        Dev::do('_before', [$this, $current]);
        if (Val::isNotNull($current)) {
            $this->setCurrent($current);
        }

        $total = (int)$this->getValue('total');
        $currentValue = (int)$this->getValue('current');
        $width = (int)$this->getValue('width');

        $progress = 0.0;
        if ($total > 0) {
            $progress = min(1, $currentValue / $total);
        }

        $filled = (int)floor($progress * $width);
        $empty = $width - $filled;

        $bar = '[' . str_repeat((string)$this->getValue('fillChar'), $filled) . str_repeat((string)$this->getValue('emptyChar'), $empty) . ']';

        $parts = [$bar];

        if ($this->getValue('showPercent')) {
            $parts[] = str_pad((string)round($progress * 100), 3, ' ', STR_PAD_LEFT) . '%';
        }

        if ($this->getValue('showCount')) {
            $parts[] = '(' . min($currentValue, $total) . '/' . $total . ')';
        }

        $output = implode(' ', $parts);
        $output = Dev::apply('_out', $output);
        $this->trigger(Event::PROCESSED, new Meta(data: $output));
        Dev::do('_after', [$output, $this]);
        return $output;
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
