<?php

namespace Flat3\OData\Expression\Node\Operator\Logical;

use Flat3\OData\Exception\NodeHandledException;
use Flat3\OData\Expression\Event\EndGroup;
use Flat3\OData\Expression\Event\Operator;
use Flat3\OData\Expression\Event\StartGroup;
use Flat3\OData\Expression\Node\Operator\Logical;

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
