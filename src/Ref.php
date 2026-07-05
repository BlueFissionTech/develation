<?php

namespace BlueFission;

use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\DevElation as Dev;

/**
 * Reference and resource primitive.
 *
 * Ref wraps references, stream resources, and lifecycle-oriented handles without
 * hiding the raw value from PHP APIs that need direct interop.
 */
class Ref extends Val implements IVal
{
    protected $_type = DataTypes::GENERIC;

    protected bool $_owned = false;
    protected bool $_bound = false;
    protected bool $_closed = false;
    protected array $_meta = [];

    public function __construct($value = null, bool $takeSnapshot = true, bool $cast = false)
    {
        parent::__construct($value, $takeSnapshot, $cast);

        $this->_meta['status'] = $this->_valid() ? 'open' : 'empty';
    }

    public static function make($value = null): IVal
    {
        $value = Dev::apply('_in', $value);
        $ref = new static($value);

        return Dev::apply('_out', $ref);
    }

    public static function resource($handle, array $options = []): self
    {
        return (new static($handle))->configure($options + ['owned' => false]);
    }

    public static function bind(&$value): self
    {
        $ref = new static(null, false, false);
        $ref->ref($value);

        return $ref;
    }

    public static function open($target = null, array $options = []): self
    {
        $mode = (string)($options['mode'] ?? 'r');
        $handle = is_resource($target) || is_object($target)
            ? $target
            : fopen((string)$target, $mode);

        $ref = static::resource($handle, $options + [
            'owned' => true,
            'mode' => $mode,
            'target' => $target,
        ]);
        $ref->_meta['status'] = $ref->_valid() ? 'open' : 'invalid';
        $ref->trigger($ref->_valid() ? Event::CONNECTED : Event::ERROR);

        return $ref;
    }

    public function ref(&$value): IVal
    {
        $value = Dev::apply('_in', $value);

        $this->_data = &$value;
        $this->_bound = true;
        $this->_closed = false;
        $this->_meta['status'] = 'bound';
        $this->trigger(Event::CHANGE);

        return $this;
    }

    public function bindTo(&$value): self
    {
        $this->ref($value);

        return $this;
    }

    public function configure(array $options): self
    {
        if (array_key_exists('owned', $options)) {
            $this->_owned = (bool)$options['owned'];
        }

        foreach ($options as $key => $value) {
            $this->_meta[$key] = $value;
        }

        if (!isset($this->_meta['status'])) {
            $this->_meta['status'] = $this->_valid() ? 'open' : 'empty';
        }

        return $this;
    }

    public function owned(?bool $owned = null): bool|self
    {
        if (is_null($owned)) {
            return $this->_owned;
        }

        $this->_owned = $owned;
        $this->_meta['owned'] = $owned;

        return $this;
    }

    public function isOwned(): bool
    {
        return $this->_owned;
    }

    public function _valid(): bool
    {
        if ($this->_closed) {
            return false;
        }

        if (is_resource($this->_data)) {
            return true;
        }

        if (is_object($this->_data)) {
            return method_exists($this->_data, 'close')
                || method_exists($this->_data, 'read')
                || method_exists($this->_data, 'write')
                || is_callable($this->_data);
        }

        return $this->_bound || !is_null($this->_data);
    }

    public function _isValid($value = null): bool
    {
        return true;
    }

    public function unwrap(): mixed
    {
        return $this->val();
    }

    public function meta(?string $key = null, mixed $value = null): mixed
    {
        if (is_null($key)) {
            return $this->_meta;
        }

        if (func_num_args() === 1) {
            return $this->_meta[$key] ?? null;
        }

        $this->_meta[$key] = $value;

        return $this;
    }

    public function read(?int $length = null): mixed
    {
        if (!$this->_valid()) {
            $this->trigger(Event::ERROR);
            return false;
        }

        if (is_resource($this->_data)) {
            $value = is_null($length) ? stream_get_contents($this->_data) : fread($this->_data, $length);
        } elseif (is_object($this->_data) && method_exists($this->_data, 'read')) {
            $value = $this->_data->read($length);
        } else {
            $value = $this->_data;
        }

        $value = Dev::apply('_out', $value);
        $this->trigger([Event::READ, Event::RECEIVED]);

        return $value;
    }

    public function line(?int $length = null): string|false
    {
        if (!$this->_valid() || !is_resource($this->_data)) {
            $this->trigger(Event::ERROR);
            return false;
        }

        $value = is_null($length) ? fgets($this->_data) : fgets($this->_data, $length);
        if ($value !== false) {
            $value = Dev::apply('_out', $value);
            $this->trigger([Event::READ, Event::RECEIVED]);
        }

        return $value;
    }

    public function write(mixed $data): int|bool
    {
        if (!$this->_valid()) {
            $this->trigger(Event::ERROR);
            return false;
        }

        $data = Dev::apply('_in', $data);
        if (is_resource($this->_data)) {
            $written = fwrite($this->_data, (string)$data);
        } elseif (is_object($this->_data) && method_exists($this->_data, 'write')) {
            $written = $this->_data->write($data);
        } else {
            $this->_data = $data;
            $written = true;
        }

        $this->trigger([Event::SENT, Event::SAVED, Event::CHANGE]);

        return $written;
    }

    public function close(): self
    {
        if ($this->_closed) {
            return $this;
        }

        if ($this->_owned) {
            if (is_resource($this->_data)) {
                fclose($this->_data);
            } elseif (is_object($this->_data) && method_exists($this->_data, 'close')) {
                $this->_data->close();
            }
        }

        $this->_closed = true;
        $this->_meta['status'] = 'closed';
        $this->trigger(Event::DISCONNECTED);

        return $this;
    }

    public function clear(): IVal
    {
        if ($this->_owned && $this->_valid()) {
            $this->close();
        }

        return parent::clear();
    }

    public function cast(): IVal
    {
        if (is_string($this->_data) && is_file($this->_data)) {
            $target = $this->_data;
            $this->_data = fopen($target, 'r');
            $this->_owned = true;
            $this->_meta['mode'] = 'r';
            $this->_meta['target'] = $target;
            $this->_meta['status'] = 'open';
            $this->trigger(Event::CONNECTED);
        }

        return $this;
    }

    public function __destruct()
    {
        if ($this->_owned && !$this->_closed && $this->_valid()) {
            $this->close();
        }
    }
}
