<?php

namespace Flat3\Lodata\Traits;

use Flat3\Lodata\Helper\Name;

/**
 * Has Name
 * @package Flat3\Lodata\Traits
 */
trait HasName
{
    /**
     * Resource identifier
     * @var Name $name
     */
    protected $name;

    /**
     * Get the name of this nominal item
     * @return string Name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     * @internal
     */
    public function __toString()
    {
        return (string) $this->name;
    }

    /**
     * Set name
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name instanceof Name ? $name : new Name($name);

        return $this;
    }
}
