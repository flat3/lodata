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

    /**
     * Get the Resource title
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Set the Resource title
     *
     * @param  string  $title
     *
     * @return $this
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function __toString()
    {
        return (string) $this->name;
    }

    /**
     * Set the Resource name
     *
     * @param $name
     * @return $this
     */
    public function setName($name): self
    {
        $this->name = $name instanceof Name ? $name : new Name($name);

        return $this;
    }
}
