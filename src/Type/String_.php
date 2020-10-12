<?php

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\PrimitiveType;

class String_ extends PrimitiveType
{
    protected $name = 'Edm.String';

    /** @var ?string $value */
    protected $value;

    public function toUrl(): string
    {
        if (null === $this->value) {
            return Constants::NULL;
        }

        return "'" . str_replace("'", "''", $this->value) . "'";
    }

    public function set($value): self
    {
        parent::set($value);

        $this->value = $this->maybeNull(null === $value ? null : (string)$value);

        return $this;
    }

    public function toJson(): ?string
    {
        return $this->value;
    }
}
