<?php

namespace BlueFission;

use BlueFission\Behavioral\Behaviors\Event;

/**
 * Inst (Instance)
 *
 * A typed IVal wrapper for PHP object values. It respects the Val contract,
 * preserves object references, and can convert the wrapped object into an Obj.
 */
class Inst extends Val implements IVal
{
    protected $_type = DataTypes::OBJECT;

    public function __construct($value = null, bool $takeSnapshot = true, bool $cast = false)
    {
        if ($value instanceof Ref) {
            $value = $value->val();
        }

        if ($value === null) {
            $value = new \stdClass();
        }

        if (is_array($value)) {
            $value = (object) $value;
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value);
            if (json_last_error() === JSON_ERROR_NONE && is_object($decoded)) {
                $value = $decoded;
            } else {
                $value = new \stdClass();
            }
        }

        parent::__construct($value, $takeSnapshot, $cast);
    }

    public function _is(): bool
    {
        return is_object($this->_data);
    }

    public function val($value = null): mixed
    {
        if (!is_null($value)) {
            if ($value instanceof Ref) {
                $value = $value->val();
            }

            if (is_array($value)) {
                $value = (object) $value;
            }

            if (is_string($value) && trim($value) !== '') {
                $decoded = json_decode($value);
                if (json_last_error() === JSON_ERROR_NONE && is_object($decoded)) {
                    $value = $decoded;
                } else {
                    $value = new \stdClass();
                }
            }

            if (!is_object($value)) {
                $this->trigger(Event::EXCEPTION);
                throw new \Exception("Value is not a valid type 'object'", 1);
            }

            $this->alter($value);

            return $this;
        }

        return $this->_data;
    }

    /**
     * Convert the wrapped instance into an Obj.
     */
    public function convert(): Obj
    {
        $obj = new Obj();

        if (is_object($this->_data)) {
            $obj->assign(get_object_vars($this->_data));
        }

        return $obj;
    }

}
