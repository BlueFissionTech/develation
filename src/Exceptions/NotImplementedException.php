<?php

namespace BlueFission\Exceptions;

/**
 * Class NotImplementedException
 *
 * @package BlueFission\Exceptions
 * @author BlueFission
 * @copyright (c) 2019-2020, BlueFission
 * @license http://opensource.org/licenses/MIT
 */
class NotImplementedException extends \Exception
{
    /**
     * NotImplementedException constructor.
     *
     * @param string $message
     */
    public function NotImplementedException($message = "")
    {
        parent::__construct($message);
    }
}
