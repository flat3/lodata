<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Parser;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Entity;
use Flat3\Lodata\Exception\Internal\ParserException;
use Flat3\Lodata\Exception\Protocol\NotImplementedException;
use Flat3\Lodata\Expression\Node;
use Flat3\Lodata\Expression\Parser;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Primitive;
use Flat3\Lodata\Type;
use Illuminate\Support\Str;

/**
 * Search
 * @link https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_BuiltinQueryFunctions
 * @package Flat3\Lodata\Expression\Parser
 */
class Search extends Parser
{
    protected $symbols = [
        Node\Operator\Comparison\Not_::class,
        Node\Operator\Comparison\And_::class,
        Node\Operator\Comparison\Or_::class,
    ];

    /**
     * Valid token types for this expression
     * @return bool
     * @throws ParserException
     */
    protected function findToken(): bool
    {
        return $this->tokenizeLeftParen() ||
            $this->tokenizeRightParen() ||
            $this->tokenizeNonOperatorString() ||
            $this->tokenizeOperator() ||
            $this->tokenizeDoubleQuotedString() ||
            $this->tokenizeSpace();
    }

    /**
     * Evaluate a search expression
     * @param  Node  $node  Node
     * @param  Entity|null  $entity  Entity
     * @return Primitive|null
     */
    public static function evaluate(Node $node, ?Entity $entity = null): ?Primitive
    {
        $left = !$node->getLeftNode() ?: self::evaluate($node->getLeftNode(), $entity);
        $right = !$node->getRightNode() ?: self::evaluate($node->getRightNode(), $entity);

        $lValue = $left instanceof Primitive ? $left->get() : $left;
        $rValue = $right instanceof Primitive ? $right->get() : $right;

        switch (true) {
            case $node instanceof Node\Literal\String_:
                /** @var DeclaredProperty[] $props */
                $props = $entity->getType()->getDeclaredProperties()->filter(function ($property) {
                    return $property->isSearchable();
                });

                foreach ($props as $prop) {
                    $value = $entity[$prop->getName()];
                    if ($value instanceof PropertyValue) {
                        if (Str::contains($value->getPrimitiveValue(), $node->getValue()->get())) {
                            return Type\Boolean::true();
                        }
                    }
                }

                return Type\Boolean::false();

            case $node instanceof Node\Operator\Comparison\And_:
                return new Type\Boolean($lValue && $rValue);

            case $node instanceof Node\Operator\Comparison\Or_:
                return new Type\Boolean($lValue || $rValue);

            case $node instanceof Node\Operator\Comparison\Not_:
                return new Type\Boolean(!$lValue);
        }

        throw new NotImplementedException();
    }
}
