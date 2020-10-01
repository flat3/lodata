<?php

namespace Flat3\OData\Drivers\Database\SQLite;

use Flat3\OData\Expression\Event;
use Flat3\OData\Expression\Event\StartFunction;
use Flat3\OData\Expression\Node\Func\StringCollection\Substring;

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
