<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Parser\Handlers;

use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Internal\ParserException;
use Flat3\Lodata\Exception\Protocol\ProtocolException;
use Flat3\Lodata\Expression\Parser;
use Flat3\Lodata\Tests\TestCase;

class MongoEntitySet extends \Flat3\Lodata\Drivers\MongoEntitySet
{
    /** @var TestCase $test */
    protected $test;

    public function __construct(TestCase $test, EntityType $entityType, ?string $identifier = 'flights')
    {
        parent::__construct($identifier, $entityType);
        $this->test = $test;
    }

    protected function assertExpression(Parser $parser, string $expression): void
    {
        try {
            $parser->pushEntitySet($this);
            $tree = $parser->generateTree($expression);
            $expression = $this->evaluateFilter($tree);

            $this->test->assertMatchesJsonSnapshot($expression);
        } catch (ParserException|ProtocolException $exception) {
            $this->test->assertMatchesExpressionSnapshot($expression, $exception->getMessage());
        }
    }

    public function assertFilterExpression(string $filter): void
    {
        $this->assertExpression($this->getFilterParser(), $filter);
    }
}