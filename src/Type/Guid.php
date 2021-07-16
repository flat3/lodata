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

    const openApiSchema = [
        'type' => Constants::OAPI_STRING,
        'format' => 'uuid',
        'pattern' => '^'.Lexer::GUID.'$',
    ];

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

    public function getEmpty(): string
    {
        return $this::stringToBinary('00000000-0000-0000-0000-000000000000');
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
