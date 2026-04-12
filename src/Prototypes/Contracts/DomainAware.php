<?php

namespace BlueFission\Prototypes\Contracts;

interface DomainAware
{
    public function domain(mixed $domain = null): mixed;
}
