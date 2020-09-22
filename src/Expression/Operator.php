<?php

namespace Flat3\OData\Expression;

use Flat3\OData\Exception\NodeHandledException;
use Flat3\OData\Expression\Event\ArgumentSeparator;
use Flat3\OData\Expression\Event\EndGroup;
use Flat3\OData\Expression\Event\Operator as OperatorEvent;
use Flat3\OData\Expression\Event\StartGroup;

abstract class Operator extends Node
{
    public const symbol = null;
    public const unary = false;
    public const rightAssociative = false;

    // http://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part2-url-conventions.html#sec_OperatorPrecedence
    public const precedence = 0;
    public const operator = null;

    /**
     * Return whether the operator is right-associative
     *
     * @return bool
     */
    public static function isRightAssociative(): bool
    {
        return static::rightAssociative;
    }

    /**
     * Return the precedence of the operator
     *
     * @return int
     */
    public static function getPrecedence(): int
    {
        return static::precedence;
    }

    /**
     * Return the symbol for this operator
     *
     * @return string
     */
    public static function getSymbol(): string
    {
        return static::symbol;
    }

    /**
     * Return whether this operator is unary
     *
     * @return bool
     */
    public static function isUnary(): bool
    {
        return static::unary;
    }

    public function compute(): void
    {
        try {
            $this->expressionEvent(new StartGroup());
            $this->getLeftNode()->compute();
            $this->expressionEvent(new OperatorEvent($this));
            $this->getRightNode()->compute();
            $this->expressionEvent(new EndGroup());
        } catch (NodeHandledException $e) {
            return;
        }
    }

    protected function computeCommaSeparatedArguments(): void
    {
        $arguments = $this->getArguments();

        while ($arguments) {
            $arg = array_shift($arguments);
            $arg->compute();

            if ($arguments) {
                $this->expressionEvent(new ArgumentSeparator());
            }
        }
    }
}
