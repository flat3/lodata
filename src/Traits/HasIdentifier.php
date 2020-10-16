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

    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier instanceof Identifier ? $identifier : new Identifier($identifier);

        return $this;
    }
}
