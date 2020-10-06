<?php

namespace Flat3\OData\Type;

use Flat3\OData\PrimitiveType;

class Decimal extends PrimitiveType
{
    protected $name = 'Edm.Decimal';

    /** @var ?double $value */
    protected $value;

    public function toJsonIeee754(): ?string
    {
        $value = $this->toJson();

        if (null === $value) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        return sprintf(sprintf('%%.%dF', max(15 - floor(log10($this->value)), 0)), $this->value);
    }

    public function toJson()
    {
        if (null === $this->value) {
            return null;
        }

        if (is_nan($this->value)) {
            return 'NaN';
        }

        if (is_infinite($this->value)) {
            return (($this->value < 0) ? '-' : '').'INF';
        }

        return $this->value;
    }

    public function toUrl(): string
    {
        if (null === $this->value) {
            return $this::URL_NULL;
        }

        if (is_nan($this->value)) {
            return 'NaN';
        }

        if (is_infinite($this->value)) {
            return (($this->value < 0) ? '-' : '').'INF';
        }

        return strtolower((string) $this->value);
    }

    public function set($value): void
    {
        if (is_float($value)) {
            $this->value = $value;

            return;
        }

        if (is_string($value)) {
            switch ($value) {
                case 'INF':
                    $this->value = INF;

                    return;

                case '-INF':
                    $this->value = -INF;

                    return;

                case 'NaN':
                    $this->value = NAN;

                    return;
            }
        }

        $this->value = $this->maybeNull(null === $value ? null : (float) $value);
    }

    protected function getEmpty()
    {
        return 0.0;
    }
}
