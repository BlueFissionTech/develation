<?php

namespace BlueFission\Prototypes\Contracts;

/**
 * DomainAware
 *
 * Marker interface for prototype carriers that can be attached to a domain or
 * shared world context.
 */
interface DomainAware
{
    /**
     * Get or set the domain reference or domain snapshot.
     *
     * @param mixed $domain
     * @return mixed
     */
    public function domain(mixed $domain = null): mixed;
}
