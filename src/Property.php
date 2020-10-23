<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Interfaces\NameInterface;
use Flat3\Lodata\Interfaces\TypeInterface;
use Flat3\Lodata\Traits\HasName;

abstract class Property implements NameInterface, TypeInterface
{
    use HasName;

    /** @var bool $nullable Whether this property is nullable */
    protected $nullable = true;

    /** @var Type $type */
    protected $type;

    public function __construct($name, Type $type)
    {
        $this->setName($name);
        $this->type = $type;
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

    public function getType(): Type
    {
        return $this->type;
    }
}
