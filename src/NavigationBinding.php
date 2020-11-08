<?php

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
     * @internal
     */
    private $path;

    /**
     * The entity set that is the target of this navigation property
     * @var EntitySet $target
     * @internal
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
     * Get the target entity set
     * @return EntitySet Entity set
     */
    public function getTarget(): EntitySet
    {
        return $this->target;
    }

    /**
     * @return string
     * @internal
     */
    public function __toString()
    {
        return $this->path->getName().'/'.$this->target->getIdentifier();
    }
}
