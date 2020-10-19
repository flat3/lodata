<?php

namespace Flat3\Lodata\Drivers\SQL;

use Flat3\Lodata\Expression\Event;

trait PostgreSQLExpression
{
    public function pgsqlFilter(Event $event): ?bool
    {
        return false;
    }
}
