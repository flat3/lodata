<?php

namespace Flat3\Lodata\Transaction;

use ArrayAccess;
use Illuminate\Support\Arr;

/**
 * Metadata Container
 * @package Flat3\Lodata\Transaction
 */
class MetadataContainer implements ArrayAccess
{
    /**
     * The metadata type in use by this container
     * @var Metadata $metadata
     */
    protected $metadata;

    /**
     * The metadata values in this container
     * @var array $values
     */
    protected $values = [];

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

    public function __construct(Metadata $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * Set a metadata property
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function set(string $key, string $value): self
    {
        $this->values[$key] = $value;
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
     * @link https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html#sec_ControllingtheAmountofControlInforma
     * @return array
     */
    public function getMetadata(): array
    {
        $metadata = $this->values;

        $requiredProperties = $this->metadata->getRequiredProperties();

        if ($requiredProperties) {
            $metadata = array_intersect_key(
                $metadata,
                array_flip(array_merge($this->requiredProperties, $requiredProperties))
            );
        }

        // Append the control information prefix to the metadata keys
        $requestedODataVersion = (string) $this->metadata->getVersion();

        $result = [];

        foreach ($metadata as $key => $value) {
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
    public function hasMetadata(): bool
    {
        return !!$this->getMetadata();
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->values);
    }

    public function offsetGet($offset)
    {
        return $this->values[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->values[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->values[$offset]);
    }
}