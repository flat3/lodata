<?php

namespace Flat3\Lodata\Drivers\SQL;

use Flat3\Lodata\Expression\Event;

trait SQLiteFilter
{
    public function sqliteFilter(Event $event): ?bool
    {
        return false;
    }
}
