<?php

namespace Flat3\Lodata\Drivers\SQL;

use Flat3\Lodata\Exception\Internal\NodeHandledException;
use Flat3\Lodata\Expression\Event;

/**
 * SQLite Filter
 * @package Flat3\Lodata\Drivers\SQL
 */
trait SQLiteFilter
{
    /**
     * SQLite-specific SQL filter generation
     * @param  Event  $event  Filter event
     * @return bool|null
     * @throws NodeHandledException
     */
    public function sqliteFilter(Event $event): ?bool
    {
        return false;
    }
}
