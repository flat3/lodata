<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers\SQL;

use Flat3\Lodata\Exception\Internal\NodeHandledException;
use Flat3\Lodata\Expression\Node;
use Flat3\Lodata\Expression\Node\Func\DateTime\Now;

/**
 * SQLite Filter
 * @package Flat3\Lodata\Drivers\SQL
 */
trait SQLiteFilter
{
    /**
     * SQLite-specific SQL filter generation
     * @param  Node  $node  Node
     * @return bool|null
     * @throws NodeHandledException
     */
    public function sqliteFilter(Node $node): ?bool
    {
        switch (true) {
            case $node instanceof Now:
                $this->addWhere("datetime('now'");

                return true;
        }

        return false;
    }
}
