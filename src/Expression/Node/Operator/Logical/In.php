<?php

namespace Flat3\Lodata\Expression\Node\Operator\Logical;

use Flat3\Lodata\Exception\Internal\NodeHandledException;
use Flat3\Lodata\Expression\Event\EndGroup;
use Flat3\Lodata\Expression\Event\Operator;
use Flat3\Lodata\Expression\Event\StartGroup;
use Flat3\Lodata\Expression\Node\Operator\Logical;

/**
 * In
 * @package Flat3\Lodata\Expression\Node\Operator\Logical
 */
class In extends Logical
{
    public const symbol = 'in';
    public const precedence = 8;
    public const unary = true;

    public function compute(): void
    {
        $this->getLeftNode()->compute();

        try {
            $this->expressionEvent(new Operator($this));
            $this->expressionEvent(new StartGroup());
            $this->computeCommaSeparatedArguments();
            $this->expressionEvent(new EndGroup());
        } catch (NodeHandledException $e) {
            return;
        }
    }
}
