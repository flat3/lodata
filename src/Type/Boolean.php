<?php

namespace Flat3\OData\Type;

use Flat3\OData\PrimitiveType;

/**
 * Class Boolean
 * @package Flat3\OData\Type
 */
class Boolean extends PrimitiveType
{
    protected $name = 'Edm.Boolean';

    /** @var ?bool $value */
    protected $value;

    public function toUrl(): string
    {
        if (null === $this->value) {
            return $this::URL_NULL;
        }

        return $this->value ? $this::URL_TRUE : $this::URL_FALSE;
    }

    public function toJson(): ?bool
    {
        return $this->value;
    }

    public function toInternal($value): void
    {
        if (is_bool($value)) {
            $this->value = $value;

            return;
        }

        if ($this::URL_TRUE === $value) {
            $this->value = true;

            return;
        }

        if ($this::URL_FALSE === $value) {
            $this->value = false;

            return;
        }

        $this->value = $this->maybeNull(null === $value ? null : (bool) $value);
    }

    protected function getEmpty()
    {
        return false;
    }
}
