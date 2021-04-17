<?php

namespace Flat3\Lodata\Expression;

use Flat3\Lodata\Exception\Internal\NodeHandledException;
use Flat3\Lodata\Expression\Event\ArgumentSeparator;
use Flat3\Lodata\Expression\Event\EndGroup;
use Flat3\Lodata\Expression\Event\Operator as OperatorEvent;
use Flat3\Lodata\Expression\Event\StartGroup;

/**
 * Operator
 * @link https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part2-url-conventions.html#sec_OperatorPrecedence
 * @package Flat3\Lodata\Expression
 */
abstract class Operator extends Node
{
    public const symbol = null;
    public const unary = false;
    public const rightAssociative = false;

    public const precedence = 0;
    public const operator = null;

    /**
     * Return whether the operator is right-associative
     * @return bool
     */
    public static function isRightAssociative(): bool
    {
        return static::rightAssociative;
    }

    /**
     * Return the precedence of the operator
     * @return int
     */
    public static function getPrecedence(): int
    {
        return static::precedence;
    }

    /**
     * Return the symbol for this operator
     * @return string
     */
    public static function getSymbol(): string
    {
        return static::symbol;
    }

    /**
     * Return whether this operator is unary
     * @return bool
     */
    public static function isUnary(): bool
    {
        return static::unary;
    }

    /**
     * Compute the value of this operator
     */
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

    /**
     * Compute the comma separated arguments provided to this operator
     */
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
