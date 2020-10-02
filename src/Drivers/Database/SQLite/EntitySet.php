<?php

namespace Flat3\OData\Drivers\Database\SQLite;

use Flat3\OData\Expression\Event;

class EntitySet extends \Flat3\OData\Drivers\Database\EntitySet
{
    public function filter(Event $event): ?bool
    {
        $handled = parent::filter($event);

        if ($handled) {
            return $handled;
        }

        return false;
    }
}
