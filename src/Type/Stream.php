<?php

namespace Flat3\OData\Type;

use Flat3\OData\Helper\Constants;
use Flat3\OData\PrimitiveType;

class Stream extends PrimitiveType
{
    protected $name = 'Edm.Stream';

    /** @var ?string $value */
    protected $value;

    public function toUrl(): string
    {
        if (null === $this->value) {
            return Constants::NULL;
        }

        return sprintf("'%s'", $this->value);
    }

    public function toJson()
    {
        if (null === $this->value) {
            return null;
        }

        return (string)$this->value;
    }

    public function set($value): void
    {
        $this->value = $this->maybeNull(null === $value ? null : $value);
    }
}
