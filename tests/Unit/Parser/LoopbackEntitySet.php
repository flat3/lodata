<?php

namespace Flat3\Lodata\Tests\Unit\Parser;

use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Exception\Internal\NodeHandledException;
use Flat3\Lodata\Expression\Node;
use Flat3\Lodata\Expression\Node\Group;
use Flat3\Lodata\Expression\Node\Literal;
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

    public function search(Node $node): ?bool
    {
        switch (true) {
            case $node instanceof Group\Start:
                $this->addSearch('(');

                return true;

            case $node instanceof Group\End:
                $this->addSearch(')');

                return true;

            case $node instanceof Or_:
                $this->addSearch('OR');

                return true;

            case $node instanceof And_:
                $this->addSearch('AND');

                return true;

            case $node instanceof Not_:
                $this->addSearch('NOT');

                return true;

            case $node instanceof Literal:
                $value = $node->getValue();

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

    public function filter(Node $node): ?bool
    {
        switch (true) {
            case $node instanceof Group\Separator:
                $this->addFilter(',');

                return true;

            case $node instanceof Group\Start:
                $this->addFilter('(');

                return true;

            case $node instanceof Group\End:
                $this->addFilter(')');

                return true;

            case $node instanceof Literal:
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
                        $this->addFilter("'".str_replace("'", "''", $node->getValue()->get())."'");
                        return true;

                    case $node instanceof Duration:
                        $this->addFilter(\Flat3\Lodata\Type\Duration::numberToDuration($node->getValue()->get()));
                        return true;
                }

                $this->addFilter($node->getValue());

                return true;

            case $node instanceof LambdaProperty:
                $this->addFilter(sprintf(
                    '%s/%s',
                    $node->getVariable(),
                    $node->getValue()
                ));
                return true;

            case $node instanceof Node\Property:
                $this->addFilter($node->getValue());
                return true;

            case $node instanceof Lambda:
                list ($lambdaExpression) = $node->getArguments();

                $this->addFilter(
                    sprintf(
                        '%s/%s(%s:',
                        $node->getNavigationProperty()->getValue(),
                        $node::symbol,
                        $node->getVariable()
                    )
                );
                $lambdaExpression->compute();
                $this->addFilter(')');

                throw new NodeHandledException();

            case $node instanceof Node\Func:
                $this->addFilter($node::symbol.'(');

                return true;

            default:
                $this->addFilter($node::symbol);

                return true;
        }
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
