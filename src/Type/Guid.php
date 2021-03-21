<?php

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Primitive;

/**
 * Guid
 * @package Flat3\Lodata\Type
 * @method static self factory($value = null, ?bool $nullable = true)
 */
class Guid extends Primitive
{
    const identifier = 'Edm.Guid';

    /** @var ?string $value */
    protected $value;

    public function toUrl(): string
    {
        if (null === $this->value) {
            return Constants::NULL;
        }

        return $this::binaryToString($this->value);
    }

    public static function binaryToString(string $value): string
    {
        return strtoupper(join('-', unpack('H8time_low/H4time_mid/H4time_hi/H4clock_seq_hi/H12clock_seq_low', $value)));
    }

    public function set($value): self
    {
        $this->value = $this->maybeNull(Lexer::patternCheck(
            Lexer::GUID,
            $value
        ) ? $this::stringToBinary($value) : (null === $value ? null : (string) $value));

        return $this;
    }

    public static function stringToBinary(string $guid): string
    {
        return pack('H*', str_replace('-', '', $guid));
    }

    public function getEmpty()
    {
        return $this::stringToBinary('00000000-0000-0000-0000-000000000000');
    }

    public function toJson(): ?string
    {
        if (null === $this->value) {
            return null;
        }

        return $this->binaryToString($this->value);
    }
}
