<?php

declare(strict_types=1);

namespace Flat3\Lodata\Traits;

use Flat3\Lodata\ComplexType;

/**
 * Has Complex Type
 * @package Flat3\Lodata\Traits
 */
trait HasComplexType
{
    /**
     * Complex type
     * @var ComplexType $type
     */
    protected $type;

    /**
     * Get the complex type
     * @return ComplexType|null
     */
    public function getType(): ?ComplexType
    {
        return $this->type;
    }

    /**
     * Set the complex type
     * @param  ComplexType  $type  Complex type
     * @return $this
     */
    public function setType(ComplexType $type)
    {
        $this->type = $type;
        return $this;
    }
}
