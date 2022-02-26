<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Parser\Handlers;

use Flat3\Lodata\Drivers\SQL\SQLExpression;
use Flat3\Lodata\Drivers\SQL\SQLSearch;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Internal\ParserException;
use Flat3\Lodata\Exception\Protocol\ProtocolException;
use Flat3\Lodata\Expression\Parser;
use Flat3\Lodata\Tests\TestCase;

abstract class SQLEntitySet extends \Flat3\Lodata\Drivers\SQLEntitySet
{
    /** @var TestCase $test */
    protected $test;

    public function __construct(TestCase $test, EntityType $entityType, ?string $identifier = 'flights')
    {
        parent::__construct($identifier, $entityType);
        $this->test = $test;
    }

    public function setTest(TestCase $test): self
    {
        $this->test = $test;

        return $this;
    }

    protected function assertExpression(Parser $parser, string $expression): void
    {
        try {
            $container = new SQLExpression($this);
            $parser->pushEntitySet($this);
            $tree = $parser->generateTree($expression);
            $container->evaluate($tree);

            $this->test->assertMatchesExpressionSnapshot(
                $expression,
                $container->getStatement(),
                $container->getParameters()
            );
        } catch (ParserException|ProtocolException $exception) {
            $this->test->assertMatchesExpressionSnapshot($expression, $exception->getMessage());
        }
    }

    public function assertFilterExpression(string $filter): void
    {
        $this->assertExpression($this->getFilterParser(), $filter);
    }

    public function assertSearchExpression(string $expression): void
    {
        try {
            $parser = $this->getSearchParser();
            $container = new SQLSearch($this);
            $parser->pushEntitySet($this);
            $tree = $parser->generateTree($expression);
            $container->evaluate($tree);

            $this->test->assertMatchesExpressionSnapshot(
                $expression,
                $container->getStatement(),
                $container->getParameters()
            );
        } catch (ParserException|ProtocolException $exception) {
            $this->test->assertMatchesExpressionSnapshot($expression, $exception->getMessage());
        }
    }

    public function assertComputeExpression(string $compute): void
    {
        $this->assertExpression($this->getComputeParser(), $compute);
    }
}