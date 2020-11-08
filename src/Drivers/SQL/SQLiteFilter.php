<?php

namespace Flat3\Lodata\Drivers\SQL;

use Flat3\Lodata\Exception\Internal\NodeHandledException;
use Flat3\Lodata\Expression\Event;
use Flat3\Lodata\Expression\Event\StartFunction;
use Flat3\Lodata\Expression\Node\Func\StringCollection\Contains;
use Flat3\Lodata\Expression\Node\Func\StringCollection\EndsWith;
use Flat3\Lodata\Expression\Node\Func\StringCollection\StartsWith;

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
            case $event instanceof StartFunction:
                $func = $event->getNode();

                switch (true) {
                    case $func instanceof Contains:
                    case $func instanceof EndsWith:
                    case $func instanceof StartsWith:
                        $arguments = $func->getArguments();
                        list($arg1, $arg2) = $arguments;

                        $arg1->compute();
                        $this->addWhere('LIKE');
                        $value = $arg2->getValue();

                        if ($func instanceof StartsWith || $func instanceof Contains) {
                            $value .= '%';
                        }

                        if ($func instanceof EndsWith || $func instanceof Contains) {
                            $value = '%'.$value;
                        }

                        $arg2->setValue($value);
                        $arg2->compute();
                        throw new NodeHandledException();
                }
        }

        return false;
    }
}
