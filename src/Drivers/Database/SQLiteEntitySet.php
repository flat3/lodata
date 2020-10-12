<?php

namespace Flat3\Lodata\Drivers\Database;

use Flat3\Lodata\Drivers\SQLEntitySet;
use Flat3\Lodata\Expression\Event;
use Flat3\Lodata\Interfaces\TransactionInterface;

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
