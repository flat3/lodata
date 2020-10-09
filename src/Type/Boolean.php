<?php

namespace Flat3\OData\Type;

use Flat3\OData\Helper\Constants;
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
            return Constants::NULL;
        }

        return $this->value ? Constants::TRUE : Constants::FALSE;
    }

    public function toJson(): ?bool
    {
        return $this->value;
    }

    public function set($value): void
    {
        if (is_bool($value)) {
            $this->value = $value;

            return;
        }

        if (Constants::TRUE === $value) {
            $this->value = true;

            return;
        }

        if (Constants::FALSE === $value) {
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
