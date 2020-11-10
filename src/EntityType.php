<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Helper\Identifier;

/**
 * Entity Type
 * @link https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530349
 * @package Flat3\Lodata
 */
class EntityType extends ComplexType
{
    /**
     * Primary key property
     * @var DeclaredProperty $key
     * @internal
     */
    protected $key;

    /**
     * Return the defined key of this entity type
     * @return DeclaredProperty|null
     */
    public function getKey(): ?DeclaredProperty
    {
        return $this->key;
    }

    /**
     * Generate a new entity type
     * @param string|Identifier $identifier
     * @return EntityType Entity Type
     */
    public static function factory($identifier)
    {
        return new self($identifier);
    }

    /**
     * Set the entity type key property
     * @param  DeclaredProperty  $key  Key property
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
