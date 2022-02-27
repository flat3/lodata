<?php

declare(strict_types=1);

namespace Flat3\Lodata\Transaction;

use ArrayAccess;
use Illuminate\Support\Arr;

/**
 * Metadata container
 * @link https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html#sec_PayloadOrderingConstraints
 * @link https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html#sec_ControllingtheAmountofControlInforma
 * @package Flat3\Lodata\Transaction
 */
class MetadataContainer implements ArrayAccess
{
    /**
     * The metadata type in use by this container
     * @var MetadataType $type
     */
    protected $type;

    /**
     * The metadata properties in this container
     * @var array $properties
     */
    protected $properties = [];

    /**
     * The additional required properties for this metadata container
     * @var array $requiredProperties
     */
    protected $requiredProperties = [];

    /**
     * The prefix applied to properties in this container
     * @var string $prefix
     */
    protected $prefix = '';

    public function __construct(MetadataType $metadata)
    {
        $this->type = $metadata;
    }

    /**
     * Set a metadata property
     * @param  string  $key
     * @param  string  $value
     * @return $this
     */
    public function set(string $key, string $value): self
    {
        $this->properties[$key] = $value;
        return $this;
    }

    /**
     * Set the prefix applied to these properties
     * @param  string  $prefix
     * @return $this
     */
    public function setPrefix(string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Add a required property on this container
     * @param  string  $property
     * @return $this
     */
    public function addRequiredProperty(string $property): self
    {
        $this->requiredProperties[] = $property;
        return $this;
    }

    /**
     * Remove a required property from this container
     * @param  string  $property
     * @return $this
     */
    public function dropRequiredProperty(string $property): self
    {
        Arr::forget($this->requiredProperties, $property);
        return $this;
    }

    /**
     * Get the metadata properties, filtered according to the type and list of required properties
     * @return array
     */
    public function getProperties(): array
    {
        $properties = $this->properties;

        $typeRequiredProperties = $this->type->getRequiredProperties();

        if ($typeRequiredProperties) {
            $properties = array_intersect_key(
                $properties,
                array_flip(array_merge($this->requiredProperties, $typeRequiredProperties))
            );
        }

        // Append the control information prefix to the metadata keys
        $requestedODataVersion = (string) $this->type->getVersion();

        $result = [];

        foreach ($properties as $key => $value) {
            if (version_compare('4.0', $requestedODataVersion, '=')) {
                $result[$this->prefix.'@odata.'.$key] = $value;
            } else {
                $result[$this->prefix.'@'.$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Whether this container will contain any properties after processing
     * @return bool
     */
    public function hasProperties(): bool
    {
        return !!$this->getProperties();
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->properties);
    }

    public function offsetGet($offset): ?string
    {
        return $this->properties[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->properties[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->properties[$offset]);
    }
}