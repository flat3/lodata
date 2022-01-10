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
    protected $precedence = 8;
    protected $unary = true;

    /** @var null|int|array Number of arguments required, or null for variadic */
    protected $argumentCount = null;

    /**
     * Validate the arguments for this function are syntactically correct
     */
    public function validateArguments(): void
    {
        if ($this->argumentCount === null) {
            return;
        }

        $target_count = $this->argumentCount;
        if (!is_array($target_count)) {
            $target_count = [$target_count];
        }

        if (in_array(count($this->getArguments()), $target_count)) {
            return;
        }

        throw new ParserException(
            sprintf(
                'The %s function requires %d arguments',
                $this->getSymbol(),
                $this->argumentCount
            )
        );
    }

    /**
     * Get the number of arguments required for this function
     * @return ?int|array Arguments
     */
    public function getArgumentCount()
    {
        return $this->argumentCount;
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
                $this->getSymbol()
            )
        );
    }
}
