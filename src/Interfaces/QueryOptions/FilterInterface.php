<?php

namespace Flat3\Lodata\Interfaces\QueryOptions;

use Flat3\Lodata\Expression\Event;

interface FilterInterface
{
    /**
     * Handle a discovered expression symbol in the filter query
     *
     * @param  Event  $event
     *
     * @return bool True if the event was handled
     */
    public function filter(Event $event): ?bool;
}