<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers\SQL;

use Flat3\Lodata\Exception\Internal\NodeHandledException;
use Flat3\Lodata\Expression\Event;
use Flat3\Lodata\Expression\Node\Func\DateTime\Now;

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
        switch (true) {
            case $event instanceof Event\StartFunction:
                $func = $event->getNode();

                switch (true) {
                    case $func instanceof Now:
                        $this->addWhere("date('now'");

                        return true;
                }
        }

        return false;
    }
}
