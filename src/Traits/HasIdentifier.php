<?php

declare(strict_types=1);

namespace Flat3\Lodata\Traits;

use Flat3\Lodata\Helper\Identifier;

/**
 * Has Identifier
 * @package Flat3\Lodata\Traits
 */
trait HasIdentifier
{
    /**
     * Resource identifier
     * @var Identifier $identifier
     */
    protected $identifier;

    /**
     * Get the identifier
     * @return Identifier Identifier
     */
    public function getIdentifier(): Identifier
    {
        return $this->identifier;
    }

    /**
     * Get the unqualified name
     * @return string Name
     */
    public function getName(): string
    {
        return $this->getIdentifier()->getName();
    }

    /**
     * Set the identifier
     * @param  string|Identifier  $identifier  Identifier
     * @return $this
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier instanceof Identifier ? $identifier : new Identifier($identifier);

        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->identifier->getQualifiedName();
    }
}
