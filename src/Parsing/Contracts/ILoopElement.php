<?php

namespace BlueFission\Parsing\Contracts;

interface ILoopElement
{
    /**
     * Execute the loop logic, returning rendered string
     * 
     * @param array $data - data set to iterate
     * @return string
     */
    public function run(array $data): string;
}
