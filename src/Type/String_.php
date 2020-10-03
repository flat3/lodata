<?php

namespace Flat3\OData\Type;

use Flat3\OData\Primitive;

class String_ extends Primitive
{
    protected $name = 'Edm.String';

    /** @var ?string $value */
    protected $value;

    public function toUrl(): string
    {
        if (null === $this->value) {
            return $this::URL_NULL;
        }

        return "'".str_replace("'", "''", $this->value)."'";
    }

    public function toInternal($value): void
    {
        $this->value = $this->maybeNull(null === $value ? null : (string) $value);
    }

    public function toJson(): ?string
    {
        return $this->value;
    }
}
