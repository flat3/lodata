<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node;

use Flat3\Lodata\Expression\Operator;

/**
 * Group
 * @package Flat3\Lodata\Expression\Node
 */
class Group extends Operator
{
    /** @var Func $func */
    private $func = null;

    /**
     * Get the function attached to this group
     *
     * @return Operator|null
     */
    public function getFunc(): ?Operator
    {
        return $this->func;
    }

    /**
     * Set the function attached to this group
     *
     * @param  Operator  $func
     */
    public function setFunc(Operator $func)
    {
        $this->func = $func;
    }
}
