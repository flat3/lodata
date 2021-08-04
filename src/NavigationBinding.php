<?php

declare(strict_types=1);

namespace Flat3\Lodata;

/**
 * Navigation Binding
 * @link https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530396
 * @package Flat3\Lodata
 */
class NavigationBinding
{
    /**
     * Navigation property representing the entity set path
     * @var NavigationProperty $path
     */
    private $path;

    /**
     * The entity set that is the target of this navigation property
     * @var EntitySet $target
     */
    private $target;

    public function __construct(NavigationProperty $path, EntitySet $target)
    {
        $this->path = $path;
        $this->target = $target;
    }

    /**
     * Get the navigation property
     * @return NavigationProperty Navigation property
     */
    public function getPath(): NavigationProperty
    {
        return $this->path;
    }

    /**
     * Update the path property of this navigation binding
     * @param  NavigationProperty  $path  Navigation property
     * @return $this Navigation binding
     */
    public function setPath(NavigationProperty $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get the target entity set
     * @return EntitySet Entity set
     */
    public function getTarget(): EntitySet
    {
        return $this->target;
    }

    /**
     * Re-target this navigation binding to a new entity set
     * @param  EntitySet  $target
     * @return $this
     */
    public function setTarget(EntitySet $target): self
    {
        $this->target = $target;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->path->getName().'/'.$this->target->getIdentifier();
    }
}
