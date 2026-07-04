<?php
namespace BlueFission\Cli\Util\Support;

use BlueFission\Arr;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\IVal;

trait ManagesUtilityState
{
    protected function setValue(string $field, mixed $value): void
    {
        $current = $this->_data[$field] ?? null;
        if ($current instanceof IVal) {
            $current->val($value);
            return;
        }

        $this->_data[$field] = $value;
    }

    protected function getValue(string $field, mixed $default = null): mixed
    {
        $current = $this->_data[$field] ?? $default;
        if ($current instanceof IVal) {
            return $current->val();
        }

        return $current;
    }

    protected function behaviorMeta(mixed $args): ?Meta
    {
        return $args instanceof Meta ? $args : null;
    }

    protected function behaviorData(mixed $args): Arr
    {
        $meta = $this->behaviorMeta($args);

        return ($meta && Arr::is($meta->data ?? null))
            ? Arr::make($meta->data)
            : Arr::make();
    }
}
