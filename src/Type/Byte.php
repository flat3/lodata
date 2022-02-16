<?php

declare(strict_types=1);

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Primitive;

/**
 * Byte
 * @package Flat3\Lodata\Type
 */
class Byte extends Numeric
{
    const identifier = 'Edm.Byte';

    const openApiSchema = [
        'type' => Constants::oapiInteger,
        'format' => 'uint8',
        'minimum' => 0,
        'maximum' => 255,
    ];

    public const format = 'C';

    /** @var ?int $value */
    protected $value;

    public function toUrl(): string
    {
        if (null === $this->value) {
            return Constants::null;
        }

        return (string) $this->value;
    }

    public function toJson(): ?int
    {
        return $this->value;
    }

    public function toScalar(): ?int
    {
        return $this->value;
    }

    public function set($value): self
    {
        $this->value = null === $value ? null : $this->repack($value);

        return $this;
    }

    protected function repack($value)
    {
        return unpack($this::format, pack('i', $value))[1];
    }

    public function get(): ?int
    {
        return parent::get();
    }

    public static function fromLexer(Lexer $lexer): Primitive
    {
        /** @phpstan-ignore-next-line */
        return new static($lexer->number());
    }
}
