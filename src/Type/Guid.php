<?php

declare(strict_types=1);

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Primitive;

/**
 * Guid
 * @package Flat3\Lodata\Type
 */
class Guid extends Primitive
{
    const identifier = 'Edm.Guid';

    const openApiSchema = [
        'type' => Constants::oapiString,
        'format' => 'uuid',
        'pattern' => '^'.Lexer::guid.'$',
    ];

    /** @var ?string $value */
    protected $value;

    public function toUrl(): string
    {
        if (null === $this->value) {
            return Constants::null;
        }

        return $this::binaryToString($this->value);
    }

    public static function binaryToString(string $value): string
    {
        return strtoupper(join('-', unpack('H8time_low/H4time_mid/H4time_hi/H4clock_seq_hi/H12clock_seq_low', $value)));
    }

    public function set($value): self
    {
        $this->value = Lexer::patternCheck(
            Lexer::guid,
            (string) $value
        ) ? $this::stringToBinary($value) : (null === $value ? null : (string) $value);

        return $this;
    }

    public static function stringToBinary(string $guid): string
    {
        return pack('H*', str_replace('-', '', $guid));
    }

    public function get(): ?string
    {
        return parent::get();
    }

    public function toJson(): ?string
    {
        if (null === $this->value) {
            return null;
        }

        return $this->binaryToString($this->value);
    }

    public static function fromLexer(Lexer $lexer): Primitive
    {
        /** @phpstan-ignore-next-line */
        return new static($lexer->guid());
    }
}
