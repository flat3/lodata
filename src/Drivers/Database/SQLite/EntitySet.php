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

        switch (true) {
            case $event instanceof StartFunction:
                $func = $event->getNode();

                switch (true) {
                    case $func instanceof Substring:
                        $this->addWhere('substr(');

                        return true;
                }
        }

        return false;
    }
}
