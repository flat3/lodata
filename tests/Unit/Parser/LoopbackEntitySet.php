<?php

namespace Flat3\Lodata\Tests\Unit\Parser;

use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Exception\Internal\NodeHandledException;
use Flat3\Lodata\Expression\Event;
use Flat3\Lodata\Expression\Event\ArgumentSeparator;
use Flat3\Lodata\Expression\Event\EndFunction;
use Flat3\Lodata\Expression\Event\EndGroup;
use Flat3\Lodata\Expression\Event\Literal;
use Flat3\Lodata\Expression\Event\Operator;
use Flat3\Lodata\Expression\Event\Property;
use Flat3\Lodata\Expression\Event\StartFunction;
use Flat3\Lodata\Expression\Event\StartGroup;
use Flat3\Lodata\Expression\Node\Literal\Boolean;
use Flat3\Lodata\Expression\Node\Literal\Date;
use Flat3\Lodata\Expression\Node\Literal\DateTimeOffset;
use Flat3\Lodata\Expression\Node\Literal\Duration;
use Flat3\Lodata\Expression\Node\Literal\Guid;
use Flat3\Lodata\Expression\Node\Literal\String_;
use Flat3\Lodata\Expression\Node\Literal\TimeOfDay;
use Flat3\Lodata\Expression\Node\Operator\Comparison\And_;
use Flat3\Lodata\Expression\Node\Operator\Comparison\Not_;
use Flat3\Lodata\Expression\Node\Operator\Comparison\Or_;
use Flat3\Lodata\Expression\Node\Operator\Lambda;
use Flat3\Lodata\Expression\Node\Property\Lambda as LambdaProperty;
use Flat3\Lodata\Interfaces\EntitySet\FilterInterface;
use Flat3\Lodata\Interfaces\EntitySet\SearchInterface;

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
                    case $node instanceof Boolean:
                        $this->addFilter($node->getValue()->get() ? 'true' : 'false');
                        return true;

                    case $node instanceof Guid:
                        $this->addFilter(\Flat3\Lodata\Type\Guid::binaryToString($node->getValue()));
                        return true;

                    case $node instanceof Date:
                        $this->addFilter($node->getValue()->get()->format('Y-m-d'));
                        return true;

                    case $node instanceof DateTimeOffset:
                        $this->addFilter($node->getValue()->get()->format('c'));
                        return true;

                    case $node instanceof TimeOfDay:
                        $this->addFilter($node->getValue()->get()->format('h:i:s'));
                        return true;

                    case $node instanceof String_:
                        $this->addFilter("'".str_replace("'", "''", $event->getValue()->get())."'");
                        return true;

                    case $node instanceof Duration:
                        $this->addFilter(\Flat3\Lodata\Type\Duration::numberToDuration($node->getValue()->get()));
                        return true;
                }

                $this->addFilter($event->getValue());

                return true;

            case $event instanceof Property:
                $node = $event->getNode();

                switch (true) {
                    case $node instanceof LambdaProperty:
                        $this->addFilter(sprintf(
                            '%s/%s',
                            $node->getVariable(),
                            $node->getValue()
                        ));
                        return true;
                }

                $this->addFilter($event->getValue());
                return true;

            case $event instanceof Operator:
                $operator = $event->getNode();

                switch (true) {
                    case $operator instanceof Lambda:
                        list ($lambdaExpression) = $operator->getArguments();

                        $this->addFilter(
                            sprintf(
                                '%s/%s(%s:',
                                $operator->getNavigationProperty()->getValue(),
                                $operator::symbol,
                                $operator->getVariable()
                            )
                        );
                        $lambdaExpression->compute();
                        $this->addFilter(')');

                        throw new NodeHandledException();

                    default:
                        $this->addFilter($operator::symbol);
                        break;
                }

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

    protected function query(): array
    {
        return [];
    }
}
