<?php

namespace Flat3\OData\Traits;

use Flat3\OData\Helper\Name;

trait HasName
{
    /** @var Name $name Resource identifier */
    protected $name;

    /** @var string $title Resource title */
    protected $title = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function __toString()
    {
        return (string) $this->name;
    }

    public function setName($name)
    {
        $this->name = $name instanceof Name ? $name : new Name($name);

        return $this;
    }
}
