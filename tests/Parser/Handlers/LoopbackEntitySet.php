<?php

namespace Flat3\Lodata\Tests\Parser\Handlers;

use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Internal\ParserException;
use Flat3\Lodata\Expression\Node;
use Flat3\Lodata\Expression\Node\Literal;
use Flat3\Lodata\Expression\Node\Literal\Boolean;
use Flat3\Lodata\Expression\Node\Literal\Date;
use Flat3\Lodata\Expression\Node\Literal\DateTimeOffset;
use Flat3\Lodata\Expression\Node\Literal\Duration;
use Flat3\Lodata\Expression\Node\Literal\Enum;
use Flat3\Lodata\Expression\Node\Literal\String_;
use Flat3\Lodata\Expression\Node\Literal\TimeOfDay;
use Flat3\Lodata\Expression\Node\Operator\Comparison\And_;
use Flat3\Lodata\Expression\Node\Operator\Comparison\Not_;
use Flat3\Lodata\Expression\Node\Operator\Comparison\Or_;
use Flat3\Lodata\Expression\Node\Operator\Lambda;
use Flat3\Lodata\Expression\Node\Property\Lambda as LambdaProperty;
use Flat3\Lodata\Expression\Operator;
use Flat3\Lodata\Expression\Parser;
use Flat3\Lodata\Interfaces\EntitySet\ComputeInterface;
use Flat3\Lodata\Interfaces\EntitySet\FilterInterface;
use Flat3\Lodata\Interfaces\EntitySet\SearchInterface;
use Flat3\Lodata\Tests\TestCase;

class LoopbackEntitySet extends EntitySet implements ComputeInterface, SearchInterface, FilterInterface
{
    public $searchBuffer;
    public $commonBuffer;
    protected $test;

    public function __construct(TestCase $test, ?EntityType $entityType = null, ?string $identifier = 'flights')
    {
        parent::__construct($identifier, $entityType);
        $this->test = $test;
    }

    public function assertExpression(Parser $parser, string $expression): void
    {
        $parser->pushEntitySet($this);

        try {
            $tree = $parser->generateTree($expression);
            $this->commonExpression($tree);

            $this->test->assertMatchesExpressionSnapshot($expression, trim($this->commonBuffer));
        } catch (ParserException $exception) {
            $this->test->assertMatchesExpressionSnapshot($expression, $exception->getMessage());
        }
    }

    public function assertFilterExpression(string $filter): void
    {
        $this->assertExpression($this->getFilterParser(), $filter);
    }

    public function assertComputeExpression(string $compute): void
    {
        $this->assertExpression($this->getComputeParser(), $compute);
    }

    public function assertSearchExpression(string $expression): void
    {
        $parser = $this->getSearchParser();
        $parser->pushEntitySet($this);

        try {
            $tree = $parser->generateTree($expression);
            $this->searchExpression($tree);

            $this->test->assertMatchesExpressionSnapshot($expression, trim($this->searchBuffer));
        } catch (ParserException $e) {
            $this->test->assertMatchesExpressionSnapshot($expression, $e->getMessage());
        }
    }

    public function searchExpression(Node $node): void
    {
        $left = $node->getLeftNode();
        $right = $node->getRightNode();

        switch (true) {
            case $node instanceof Operator:
                $this->addSearch('( ');

                switch (true) {
                    case $node instanceof Or_:
                        $this->searchExpression($left);
                        $this->addSearch(' OR ');
                        $this->searchExpression($right);
                        break;

                    case $node instanceof And_:
                        $this->searchExpression($left);
                        $this->addSearch(' AND ');
                        $this->searchExpression($right);
                        break;

                    case $node instanceof Not_:
                        $this->addSearch('NOT ');
                        $this->searchExpression($left);
                        break;
                }

                $this->addSearch(' )');
                break;

            case $node instanceof Literal:
                $value = $node->getValue();
                $value = sprintf('"%s"', str_replace('"', '""', $value));
                $this->addSearch($value);
                break;
        }
    }

    public function addSearch(string $s)
    {
        $this->searchBuffer .= $s;
    }

    public function commonExpression(Node $node): void
    {
        switch (true) {
            case $node instanceof Literal:
                switch (true) {
                    case $node instanceof Boolean:
                        $this->addCommon($node->getValue()->get() ? 'true' : 'false');
                        return;

                    case $node instanceof Date:
                        $this->addCommon($node->getValue()->get()->format('Y-m-d'));
                        return;

                    case $node instanceof DateTimeOffset:
                        $this->addCommon($node->getValue()->get()->format('c'));
                        return;

                    case $node instanceof TimeOfDay:
                        $this->addCommon($node->getValue()->get()->format('h:i:s'));
                        return;

                    case $node instanceof String_:
                        $this->addCommon("'".str_replace("'", "''", $node->getValue()->get())."'");
                        return;

                    case $node instanceof Duration:
                        $this->addCommon(\Flat3\Lodata\Type\Duration::numberToDuration($node->getValue()->get()));
                        return;

                    case $node instanceof Enum:
                        $this->addCommon($node->getValue()->toUrl());
                        return;

                    case $node instanceof Literal\Null_:
                        $this->addCommon('null');
                        return;
                }

                $this->addCommon($node->getValue());
                return;

            case $node instanceof LambdaProperty:
                $this->addCommon(sprintf(
                    '%s/%s',
                    $node->getVariable(),
                    $node->getValue()
                ));
                return;

            case $node instanceof Node\Property:
                $this->addCommon($node->getValue());
                return;

            case $node instanceof Lambda:
                list ($lambdaExpression) = $node->getArguments();

                $this->addCommon(
                    sprintf(
                        '%s/%s(%s:',
                        $node->getNavigationProperty()->getValue(),
                        $node::symbol,
                        $node->getVariable()
                    )
                );
                $this->commonExpression($lambdaExpression);
                $this->addCommon(' )');
                return;

            case $node instanceof Node\Func:
                $node->validateArguments();
                $this->addCommon($node::symbol.'(');
                $this->addCommaSeparatedArguments($node);
                $this->addCommon(')');
                return;

            case $node instanceof Not_:
                $this->addCommon('( ');
                $this->addCommon($node::symbol.' ');
                $this->commonExpression($node->getLeftNode());
                $this->addCommon(' )');
                return;

            case $node instanceof Node\Operator\Logical\In:
                $this->commonExpression($node->getLeftNode());
                $this->addCommon(sprintf(" %s ", $node::symbol));
                $this->addCommon('(');
                $this->addCommaSeparatedArguments($node);
                $this->addCommon(')');
                return;

            case $node instanceof Operator:
                $this->addCommon('( ');
                $this->commonExpression($node->getLeftNode());
                $this->addCommon(sprintf(" %s ", $node::symbol));
                $this->commonExpression($node->getRightNode());
                $this->addCommon(' )');
                return;

            default:
                $this->addCommon($node::symbol);
        }
    }

    public function addCommaSeparatedArguments(Node $node)
    {
        $arguments = $node->getArguments();

        while ($arguments) {
            $arg = array_shift($arguments);
            $this->addCommon(' ');
            $this->commonExpression($arg);

            if ($arguments) {
                $this->commonBuffer .= ',';
            } else {
                $this->commonBuffer .= ' ';
            }
        }
    }

    public function addCommon(string $s)
    {
        $this->commonBuffer .= $s;
    }
}
