<?php

namespace Flat3\Lodata\Expression\Node;

use Flat3\Lodata\Expression\Operator;

class LeftParen extends Group
{
    public const symbol = '(';

    /** @var Func $func */
    private $func = null;

    /**
     * Get the function attached to this parenthesis
     *
     * @return Operator|null
     */
    public function getFunc(): ?Operator
    {
        return $this->func;
    }

    /**
     * Set the function attached to this parenthesis
     *
     * @param  Operator  $func
     */
    public function setFunc(Operator $func)
    {
        $this->func = $func;
    }
}
