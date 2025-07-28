<?php

namespace BlueFission\Parsing\Elements;

use BlueFission\Parsing\Element;
use BlueFission\Parsing\Block;
use BlueFission\Parsing\Contracts\IRenderableElement;
use BlueFission\Data\FileSystem;

class AppElement extends Element implements IRenderableElement
{
    protected array $units = [];

    public function render(): string
    {
        return '';
    }

    public function addUnit(string $name, string $unit): void
    {
        $this->units[$name] = $unit;
    }

    public function findUnit(string $name): string
    {
        return $this->units[$name] ?? '';
    }

    public function build(): string
    {
        return parent::render();
    }
}