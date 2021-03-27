<?php

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
     * @internal
     */
    protected $identifier;

    /**
     * Get the identifier
     * @return string Identifier
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return string
     * @internal
     */
    public function __toString(): string
    {
        return (string) $this->identifier;
    }

    /**
     * Get the unqualified name
     * @return string Name
     */
    public function getName(): string
    {
        return $this->identifier->getName();
    }

    /**
     * Get the namespace
     * @return string Namespace
     */
    public function getNamespace(): string
    {
        return $this->identifier->getNamespace();
    }

    /**
     * Get the resolved name of this item based on the provided namespace
     * @param  string  $namespace  Namespace
     * @return string
     */
    public function getResolvedName(string $namespace): string
    {
        if ($this->identifier->getNamespace() === $namespace) {
            return $this->getName();
        }

        return $this->getIdentifier();
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
}
