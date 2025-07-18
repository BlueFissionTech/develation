<?php

namespace BlueFission\Parsing\Contracts;

interface IConditionElement
{
    /**
     * Evaluate the condition and return whether to render block
     * 
     * @return bool
     */
    public function evaluate(): bool;
}
