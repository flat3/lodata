<?php

namespace Flat3\OData\Drivers\Database;

use Flat3\OData\Drivers\SQLEntitySet;
use Flat3\OData\Expression\Event;
use Flat3\OData\Interfaces\TransactionInterface;

class SQLiteEntitySet extends SQLEntitySet implements TransactionInterface
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
