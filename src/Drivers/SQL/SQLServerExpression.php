<?php

namespace Flat3\Lodata\Drivers\SQL;

use Flat3\Lodata\Expression\Event;

trait SQLServerExpression
{
    public function sqlsrvFilter(Event $event): ?bool
    {
        return false;
    }
}
