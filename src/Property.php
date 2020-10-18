<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Interfaces\DynamicPropertyInterface;
use Flat3\Lodata\Interfaces\NameInterface;
use Flat3\Lodata\Interfaces\TypeInterface;
use Flat3\Lodata\Traits\HasName;
use Flat3\Lodata\Traits\HasType;

abstract class Property implements TypeInterface, NameInterface
{
    use HasName;
    use HasType;

    /** @var bool $nullable Whether this property is nullable */
    protected $nullable = true;

    public function __construct($name, TypeInterface $type)
    {
        $this->setName($name);
        $this->type = $type;

        if (
            !$this instanceof DeclaredProperty &&
            !$this instanceof DynamicPropertyInterface &&
            !$this instanceof NavigationProperty
        ) {
            throw new InternalServerErrorException(
                sprintf('A dynamic property must implement %s', DynamicPropertyInterface::class)
            );
        }
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * Set whether this property can be made null
     *
     * @param  bool  $nullable
     *
     * @return $this
     */
    public function setNullable(bool $nullable): self
    {
        $this->nullable = $nullable;

        return $this;
    }
}
