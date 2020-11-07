<?php

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Primitive;

/**
 * Class Boolean
 * @package Flat3\Lodata\Type
 */
class Boolean extends Primitive
{
    const identifier = 'Edm.Boolean';

    /** @var ?bool $value */
    protected $value;

    public function toUrl(): string
    {
        if (null === $this->value) {
            return Constants::NULL;
        }

        return $this->value ? Constants::TRUE : Constants::FALSE;
    }

    public function toJson(): ?bool
    {
        return $this->value;
    }

    public function set($value): self
    {
        if (is_bool($value)) {
            $this->value = $value;

            return $this;
        }

        if (Constants::TRUE === $value) {
            $this->value = true;

            return $this;
        }

        if (Constants::FALSE === $value) {
            $this->value = false;

            return $this;
        }

        $this->value = $this->maybeNull(null === $value ? null : (bool) $value);

        return $this;
    }

    protected function getEmpty()
    {
        return false;
    }
}
