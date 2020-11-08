<?php

namespace Flat3\Lodata\Expression\Node\Operator\Comparison;

use Flat3\Lodata\Exception\Internal\NodeHandledException;
use Flat3\Lodata\Expression\Event\EndGroup;
use Flat3\Lodata\Expression\Event\Operator;
use Flat3\Lodata\Expression\Event\StartGroup;
use Flat3\Lodata\Expression\Node\Operator\Comparison;

/**
 * Not
 * @package Flat3\Lodata\Expression\Node\Operator\Comparison
 */
class Not_ extends Comparison
{
    public const symbol = 'not';
    public const unary = true;
    public const precedence = 7;

    public function compute(): void
    {
        try {
            $this->expressionEvent(new StartGroup());
            $this->expressionEvent(new Operator($this));
            $this->getLeftNode()->compute();
            $this->expressionEvent(new EndGroup());
        } catch (NodeHandledException $e) {
            return;
        }
    }
}
