<?php

namespace BlueFission\Parsing\Contracts;

interface IRenderableElement
{
    /**
     * Render the element to string.
     *
     * @return string
     */
    public function render(): string;
}
