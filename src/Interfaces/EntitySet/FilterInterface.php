<?php

namespace Flat3\Lodata\Interfaces\EntitySet;

use Flat3\Lodata\Expression\Event;

/**
 * Filter Interface
 * @package Flat3\Lodata\Interfaces\EntitySet
 */
interface FilterInterface
{
    /**
     * Handle a discovered expression symbol in the filter query
     * @param  Event  $event  Event
     * @return bool True if the event was handled
     */
    public function filter(Event $event): ?bool;
}