<?php

namespace Flat3\Lodata\Tests\Unit\Parser;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Expression\Parser\Search;
use Flat3\Lodata\Primitive;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type;
use RuntimeException;

class SearchExpressionTest extends TestCase
{
    public function test_1()
    {
        $this->assertTrueExpression('wor');
    }

    public function test_2()
    {
        $this->assertFalseExpression('there');
    }

    public function assertTrueExpression($expression): void
    {
        $this->assertTrue($this->evaluate($expression));
    }

    public function assertFalseExpression($expression): void
    {
        $this->assertFalse($this->evaluate($expression));
    }

    public function assertNullExpression($expression): void
    {
        $this->assertNull($this->evaluate($expression));
    }

    public function assertSameExpression($expected, $expression): void
    {
        $this->assertSame($expected, $this->evaluate($expression));
    }

    public function assertBadExpression($expression): void
    {
        try {
            $this->evaluate($expression);
            throw new RuntimeException('Failed to throw exception');
        } catch (BadRequestException $e) {
            return;
        }
    }

    public function evaluate(string $expression)
    {
        $parser = new Search();
        $tree = $parser->generateTree($expression);

        $type = new EntityType('a');
        $type->addProperty(
            (new DeclaredProperty('a', Type::string()))
                ->setSearchable()
        );

        $entity = new Entity();
        $entity->setType($type);
        $entity['a'] = 'hello world';

        $result = $tree->evaluateSearchExpression($entity);

        switch (true) {
            case $result instanceof Primitive:
                return $result->get();

            case $result === null:
                return null;
        }

        throw new RuntimeException('Incorrect type returned');
    }
}