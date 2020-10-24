<?php

namespace Flat3\Lodata;

class EntityType extends ComplexType
{
    /** @var DeclaredProperty $key Primary key property */
    protected $key;

    /**
     * Return the defined key
     *
     * @return DeclaredProperty|null
     */
    public function getKey(): ?DeclaredProperty
    {
        return $this->key;
    }

    /**
     * Set the key property by name
     *
     * @param  DeclaredProperty  $key
     *
     * @return $this
     */
    public function setKey(DeclaredProperty $key): self
    {
        $this->addProperty($key);

        // Key property is not nullable
        $key->setNullable(false);

        // Key property should be marked keyable
        $key->setAlternativeKey(true);

        $this->key = $key;

        return $this;
    }
}
