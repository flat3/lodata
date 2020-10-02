<?php

namespace Flat3\OData\Tests;

use Flat3\OData\Entity;
use Flat3\OData\Expression\Event;
use Flat3\OData\Expression\Event\ArgumentSeparator;
use Flat3\OData\Expression\Event\EndFunction;
use Flat3\OData\Expression\Event\EndGroup;
use Flat3\OData\Expression\Event\Field;
use Flat3\OData\Expression\Event\Literal;
use Flat3\OData\Expression\Event\Operator;
use Flat3\OData\Expression\Event\StartFunction;
use Flat3\OData\Expression\Event\StartGroup;
use Flat3\OData\Expression\Node\Literal\String_;
use Flat3\OData\Expression\Node\Operator\Comparison\And_;
use Flat3\OData\Expression\Node\Operator\Comparison\Not_;
use Flat3\OData\Expression\Node\Operator\Comparison\Or_;
use Flat3\OData\Interfaces\FilterInterface;
use Flat3\OData\Interfaces\SearchInterface;
use Flat3\OData\Primitive;
use Flat3\OData\Resource\EntitySet;

class LoopbackEntitySet extends EntitySet implements SearchInterface, FilterInterface
{
    public $searchBuffer;
    public $filterBuffer;

    public function search(Event $event): ?bool
    {
        switch (true) {
            case $event instanceof StartGroup:
                $this->addSearch('(');

                return true;

            case $event instanceof EndGroup:
                $this->addSearch(')');

                return true;

            case $event instanceof Operator:
                $node = $event->getNode();

                switch (true) {
                    case $node instanceof Or_:
                        $this->addSearch('OR');

                        return true;

                    case $node instanceof And_:
                        $this->addSearch('AND');

                        return true;

                    case $node instanceof Not_:
                        $this->addSearch('NOT');

                        return true;
                }
                break;

            case $event instanceof Literal:
                $value = $event->getValue();

                $value = sprintf('"%s"', str_replace('"', '""', $value));

                $this->addSearch($value);

                return true;
        }

        return false;
    }

    public function addSearch(string $s)
    {
        $this->searchBuffer .= ' '.$s;
    }

    public function filter(Event $event): ?bool
    {
        switch (true) {
            case $event instanceof ArgumentSeparator:
                $this->addFilter(',');

                return true;

            case $event instanceof EndGroup:
            case $event instanceof EndFunction:
                $this->addFilter(')');

                return true;

            case $event instanceof Literal:
                $node = $event->getNode();
                switch (true) {
                    case $node instanceof String_:
                        $this->addFilter("'".str_replace("'", "''", $event->getValue())."'");

                        return true;
                }

                $this->addFilter($event->getValue());

                return true;

            case $event instanceof Field:
                $this->addFilter($event->getValue());

                return true;

            case $event instanceof Operator:
                $operator = $event->getNode();

                $this->addFilter($operator::symbol);

                return true;

            case $event instanceof StartFunction:
                $func = $event->getNode();

                $this->addFilter($func::symbol.'(');

                return true;

            case $event instanceof StartGroup:
                $this->addFilter('(');

                return true;
        }

        return false;
    }

    public function addFilter(string $s)
    {
        $this->filterBuffer .= ' '.$s;
    }

    public function next(): void
    {
    }

    public function key(): ?string
    {
        return '';
    }

    public function valid(): bool
    {
        return false;
    }

    protected function generate(): void
    {
        $this->results = [];
    }
}
