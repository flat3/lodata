<?php

namespace Flat3\OData\Type;

class Byte extends PrimitiveType
{
    protected $name = 'Edm.Byte';
    public const format = 'C';

    /** @var ?int $value */
    protected $value;

    public function toUrl(): string
    {
        if (null === $this->value) {
            return $this::URL_NULL;
        }

        return (string) $this->value;
    }

    public function toJson(): ?int
    {
        return $this->value;
    }

    public function toInternal($value): void
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
