<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Group;

use Flat3\Lodata\Expression\Node\Func;
use Flat3\Lodata\Expression\Node\Group;
use Flat3\Lodata\Expression\Operator;

/**
 * Start of a group
 * @package Flat3\Lodata\Expression\Node\Group
 */
class Start extends Group
{
    public const symbol = '(';

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
