<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression;

use Flat3\Lodata\Exception\Protocol\NotImplementedException;

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
     * Throw an exception if this node cannot be handled
     * @return void
     */
    public function notImplemented(): void
    {
        throw new NotImplementedException(
            'unsupported_operator',
            sprintf(
                'This entity set does not support the operator "%s"',
                $this::symbol
            )
        );
    }
}
