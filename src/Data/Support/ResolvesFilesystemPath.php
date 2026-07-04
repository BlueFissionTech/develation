<?php

namespace BlueFission\Data\Support;

use BlueFission\Arr;
use BlueFission\Collections\Collection;
use BlueFission\DevElation as Dev;
use BlueFission\Val;

/**
 * Resolves explicit filesystem paths or hierarchical label paths.
 */
trait ResolvesFilesystemPath
{
    /**
     * Resolve an explicit path or the current hierarchical label path.
     *
     * @param string|null $path
     * @param string $separator
     * @return string|null
     */
    protected function targetPath(?string $path = null, string $separator = DIRECTORY_SEPARATOR): ?string
    {
        if (Val::isNotNull($path)) {
            return Dev::apply('_in', $path);
        }

        $segments = (new Collection($this->path()))
            ->filter(fn ($segment) => Val::isNotNull($segment) && $segment !== '')
            ->contents();

        if (Arr::isEmpty($segments)) {
            return null;
        }

        return Dev::apply('_in', Arr::make($segments)->join($separator)->val());
    }
}
