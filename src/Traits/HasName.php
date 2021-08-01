<?php

declare(strict_types=1);

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
        return (string) $this->name;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->name;
    }

    /**
     * Set name
     * @param  string|Name  $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name instanceof Name ? $name : new Name($name);

        return $this;
    }
}
