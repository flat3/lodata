<?php

namespace Flat3\Lodata\Interfaces\EntitySet;

use Flat3\Lodata\Expression\Event;

interface SearchInterface
{
    /**
     * Handle a discovered expression symbol in the search query
     *
     * @param  Event  $event
     *
     * @return bool True if the event was handled
     */
    public function search(Event $event): ?bool;
}