<?php

namespace Flat3\OData\Type;

use Flat3\OData\Helper\Constants;
use Flat3\OData\PrimitiveType;

class Byte extends PrimitiveType
{
    protected $name = 'Edm.Byte';
    public const format = 'C';

    /** @var ?int $value */
    protected $value;

    public function toUrl(): string
    {
        if (null === $this->value) {
            return Constants::NULL;
        }

        return (string) $this->value;
    }

    public function toJson(): ?int
    {
        return $this->value;
    }

    public function set($value): void
    {
        $this->value = $this->maybeNull(null === $value ? null : $this->repack($value));
    }

    protected function repack($value)
    {
        return unpack($this::format, pack('i', $value))[1];
    }

    protected function getEmpty()
    {
        return 0;
    }
}
