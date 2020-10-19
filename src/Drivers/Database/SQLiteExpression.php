<?php

namespace Flat3\Lodata\Drivers\Database;

use Flat3\Lodata\Expression\Event;

trait SQLiteExpression
{
    public function sqliteFilter(Event $event): ?bool
    {
        return false;
    }
}
