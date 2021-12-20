<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node;

use Flat3\Lodata\Exception\Internal\ParserException;
use Flat3\Lodata\Exception\Protocol\NotImplementedException;
use Flat3\Lodata\Expression\Operator;

/**
 * Function
 * @package Flat3\Lodata\Expression\Node
 */
class Func extends Operator
{
    public const precedence = 8;
    public const unary = true;

    /** @var null|int Number of arguments required, or null for variadic */
    public const arguments = null;

    /**
     * Validate the arguments for this function are syntactically correct
     */
    public function validateArguments(): void
    {
        if (static::arguments === null) {
            return;
        }

        $target_count = static::arguments;
        if (!is_array($target_count)) {
            $target_count = [$target_count];
        }

        if (in_array(count($this->getArguments()), $target_count)) {
            return;
        }

        throw new ParserException(sprintf('The %s function requires %d arguments', static::symbol, static::arguments));
    }

    /**
     * Throw an exception if this node cannot be handled
     * @return void
     */
    public function notImplemented(): void
    {
        throw new NotImplementedException(
            'unsupported_function',
            sprintf(
                'This entity set does not support the function "%s"',
                $this::symbol
            )
        );
    }
}
