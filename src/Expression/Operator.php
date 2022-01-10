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
    protected $symbol = null;
    protected $unary = false;
    protected $rightAssociative = false;

    protected $precedence = 0;
    protected $operator = null;

    /**
     * Return whether the operator is right-associative
     * @return bool
     */
    public function isRightAssociative(): bool
    {
        return $this->rightAssociative;
    }

    /**
     * Return the precedence of the operator
     * @return int
     */
    public function getPrecedence(): int
    {
        return $this->precedence;
    }

    /**
     * Return the symbol for this operator
     * @return string
     */
    public function getSymbol(): string
    {
        return $this->symbol;
    }

    /**
     * Return whether this operator is unary
     * @return bool
     */
    public function isUnary(): bool
    {
        return $this->unary;
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
                $this->symbol
            )
        );
    }
}
