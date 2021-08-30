<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Operator\Logical;

use Flat3\Lodata\Exception\Internal\NodeHandledException;
use Flat3\Lodata\Expression\Node\Group;
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
            $this->emit($this);
            $this->emit(new Group\Start($this->parser));
            $this->computeCommaSeparatedArguments();
            $this->emit(new Group\End($this->parser));
        } catch (NodeHandledException $e) {
            return;
        }
    }
}
