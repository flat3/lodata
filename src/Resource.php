<?php

namespace Flat3\OData;

abstract class Resource
{
    public const EDM_TYPE = 'Unknown';

    /** @var Identifier $identifier Resource identifier */
    protected $identifier;

    /** @var string $title Resource title */
    protected $title = null;

    public function __construct($identifier)
    {
        $this->identifier = $identifier instanceof Identifier ? $identifier : new Identifier($identifier);
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

    /**
     * Get the EDM type of this resource
     *
     * @return string
     */
    public function getEdmType(): string
    {
        return $this::EDM_TYPE;
    }

    public function __toString()
    {
        return $this->getIdentifier()->get();
    }

    /**
     * Get the Resource identifier
     *
     * @return Identifier
     */
    public function getIdentifier(): Identifier
    {
        return $this->identifier;
    }

    /**
     * Set the Resource name
     *
     * @param  Identifier  $identifier
     *
     * @return $this
     */
    public function setIdentifier(Identifier $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }
}
