<?php

namespace BlueFission\Parsing\Contracts;

interface IAttributeAwareElement
{
    /**
     * Returns list of supported or required attributes
     *
     * @return array
     */
    public static function expectedAttributes(): array;

    /**
     * Validate or transform given attributes
     *
     * @param array $attributes
     * @return array
     */
    public function parseAttributes(array $attributes): array;
}
