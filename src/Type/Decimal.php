<?php

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Primitive;

/**
 * Decimal
 * @package Flat3\Lodata\Type
 * @method static self factory($value = null, ?bool $nullable = true)
 */
class Decimal extends Primitive
{
    const identifier = 'Edm.Decimal';

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
            return Constants::NULL;
        }

        if (is_nan($this->value)) {
            return 'NaN';
        }

        if (is_infinite($this->value)) {
            return (($this->value < 0) ? '-' : '').'INF';
        }

        return strtolower((string) $this->value);
    }

    public function set($value): self
    {
        if (is_float($value)) {
            $this->value = $value;

            return $this;
        }

        if (is_string($value)) {
            switch ($value) {
                case 'INF':
                    $this->value = INF;

                    return $this;

                case '-INF':
                    $this->value = -INF;

                    return $this;

                case 'NaN':
                    $this->value = NAN;

                    return $this;
            }
        }

        $this->value = $this->maybeNull(null === $value ? null : (float) $value);

        return $this;
    }

    protected function getEmpty()
    {
        return 0.0;
    }
}
