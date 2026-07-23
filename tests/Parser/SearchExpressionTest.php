<?php

namespace Flat3\Lodata\Tests\Parser;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Expression\Parser\Search;
use Flat3\Lodata\Primitive;
use Flat3\Lodata\Type;
use RuntimeException;

class SearchExpressionTest extends Expression
{
    public function test_1()
    {
        $this->assertTrueExpression('wor');
    }

    public function test_2()
    {
        $this->assertFalseExpression('there');
    }

    public function test_3()
    {
        $this->assertTrueExpression('hell or wor');
    }

    public function test_4()
    {
        $this->assertTrueExpression('hell and wor');
    }

    public function test_5()
    {
        $this->assertFalseExpression('hell and ther');
    }

    public function test_6()
    {
        $this->assertTrueExpression('hell or ther not th');
    }

    public function test_7()
    {
        $this->assertTrueExpression('hell or ther not world');
    }

    public function test_8()
    {
        $this->assertFalseExpression('ornot or andthis or ther not th');
    }

    public function test_9()
    {
        $this->assertFalseExpression('ornot OR ANDthis or ther NOT th');
    }

    public function test_implicit_and()
    {
        $this->assertTrueExpression('hell wor');
    }

    public function test_implicit_and_false()
    {
        $this->assertFalseExpression('hell ther');
    }

    public function test_implicit_and_precedence()
    {
        $this->assertTrueExpression('ther or hell wor');
    }

    public function test_implicit_and_not_false()
    {
        $this->assertFalseExpression('hell not wor');
    }

    public function test_implicit_and_not_true()
    {
        $this->assertTrueExpression('hell not ther');
    }

    public function test_implicit_and_group()
    {
        $this->assertTrueExpression('(ther or hell) wor');
    }

    public function test_implicit_and_phrase()
    {
        $this->assertTrueExpression('"hello world" wor');
    }

    public function test_implicit_and_multiple_whitespace()
    {
        $this->assertTrueExpression("hell  \t wor");
    }

    public function test_implicit_and_operator_prefix_words()
    {
        $this->assertFalseExpression('andthis ornot');
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

        $result = Search::evaluate($tree, $entity);

        switch (true) {
            case $result instanceof Primitive:
                return $result->get();

            case $result === null:
                return null;
        }

        throw new RuntimeException('Incorrect type returned');
    }
}
