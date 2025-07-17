<?php

namespace BlueFission\Parsing\Contracts;

interface IExecutableElement
{
    /**
     * Run a functional operation.
     * 
     * @return mixed
     */
    public function execute(): mixed;
}
