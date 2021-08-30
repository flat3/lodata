<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node;

use Flat3\Lodata\Exception\Internal\NodeHandledException;
use Flat3\Lodata\Exception\Internal\ParserException;
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

    public function compute(): void
    {
        $this->validateArguments();

        try {
            $this->emit($this);
            $this->computeCommaSeparatedArguments();
            $this->emit(new Group\End($this->parser));
        } catch (NodeHandledException $e) {
            return;
        }
    }

    /**
     * Validate the arguments for this function are syntactically correct
     */
    protected function validateArguments(): void
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
}
