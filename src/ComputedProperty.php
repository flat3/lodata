<?php

declare(strict_types=1);

namespace Flat3\Lodata;

use Flat3\Lodata\Type\Untyped;

/**
 * Computed Property
 * @link https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_SystemQueryOptioncompute
 * @package Flat3\Lodata
 */
class ComputedProperty extends Property
{
    protected $filterable = true;

    /**
     * Property expression
     * @var string $expression Expression
     */
    protected $expression;

    public function __construct($name)
    {
        parent::__construct($name, new Untyped);
    }

    /**
     * Set the expression used by this property
     * @param  string  $expression  Expression
     * @return $this
     */
    public function setExpression(string $expression): self
    {
        $this->expression = $expression;

        return $this;
    }

    /**
     * Get the expression used by this property
     * @return string Expression
     */
    public function getExpression(): string
    {
        return $this->expression;
    }
}
