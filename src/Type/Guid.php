<?php

namespace Flat3\OData\Type;

use Flat3\OData\PathComponent\Primitive;
use Flat3\OData\Expression\Lexer;

class Guid extends Primitive
{
    protected $name = 'Edm.Guid';

    /** @var ?string $value */
    protected $value;

    public function toUrl(): string
    {
        if (null === $this->value) {
            return $this::URL_NULL;
        }

        return $this->binaryToString($this->value);
    }

    public function binaryToString(string $value): string
    {
        return strtoupper(join('-', unpack('H8time_low/H4time_mid/H4time_hi/H4clock_seq_hi/H12clock_seq_low', $value)));
    }

    public function toInternal($value): void
    {
        $this->value = $this->maybeNull(Lexer::patternCheck(
            Lexer::GUID,
            $value
        ) ? $this->stringToBinary($value) : (null === $value ? null : (string) $value));
    }

    public function stringToBinary(string $guid): string
    {
        return pack('H*', str_replace('-', '', $guid));
    }

    public function getEmpty()
    {
        return $this->stringToBinary('00000000-0000-0000-0000-000000000000');
    }

    public function toJson(): ?string
    {
        if (null === $this->value) {
            return null;
        }

        return $this->binaryToString($this->value);
    }
}
