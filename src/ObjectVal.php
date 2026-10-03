<?php

namespace BlueFission;

/**
 * A typed value for PHP objects without the dynamic field behavior of Obj.
 */
class ObjectVal extends Val implements IVal
{
    protected $_type = DataTypes::OBJECT;

    public function _is(): bool
    {
        return is_object($this->_data);
    }
}
