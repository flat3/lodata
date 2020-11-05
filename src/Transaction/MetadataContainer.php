<?php

namespace Flat3\Lodata\Transaction;

use ArrayAccess;
use Illuminate\Support\Arr;

class MetadataContainer implements ArrayAccess
{
    /** @var Metadata $metadata */
    protected $metadata;

    /** @var array $values */
    protected $values = [];

    /** @var array $requiredProperties */
    protected $requiredProperties = [];

    /** @var string $prefix */
    protected $prefix = '';

    public function __construct(Metadata $metadata)
    {
        $this->metadata = $metadata;
    }

    public function set($key, $value): self
    {
        $this->values[$key] = $value;
        return $this;
    }

    public function setPrefix(string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function addRequiredProperty(string $property): self
    {
        $this->requiredProperties[] = $property;
        return $this;
    }

    public function dropRequiredProperty(string $property): self
    {
        Arr::forget($this->requiredProperties, $property);
        return $this;
    }

    /**
     * Filter the response metadata based on the requested metadata type
     *
     * https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html#sec_ControllingtheAmountofControlInforma
     *
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