<?php

namespace Flat3\Lodata\Traits;

use Flat3\Lodata\Helper\Identifier;

trait HasIdentifier
{
    /** @var Identifier $identifier Resource identifier */
    protected $identifier;

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function __toString()
    {
        return (string) $this->identifier;
    }

    public function getName(): string
    {
        return $this->identifier->getName();
    }

    public function getNamespace(): string
    {
        return $this->identifier->getNamespace();
    }

    public function getResolvedName(string $namespace): string
    {
        if ($this->identifier->getNamespace() === $namespace) {
            return $this->getName();
        }

        return $this->getIdentifier();
    }

    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier instanceof Identifier ? $identifier : new Identifier($identifier);

        return $this;
    }
}
