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

    /** @var ?string $value */
    protected $value;

    public function toUrl(): string
    {
        if (null === $this->value) {
            return Constants::null;
        }

        return $this->value;
    }

    public function set($value): self
    {
        $this->value = Lexer::patternCheck(
            Lexer::guid,
            (string) $value
        ) ? strtoupper($value) : (null === $value ? null : (string) $value);

        return $this;
    }

    public function toJson(): ?string
    {
        if (null === $this->value) {
            return null;
        }

        return $this->value;
    }

    public function toMixed(): ?string
    {
        return $this->value;
    }

    public static function fromLexer(Lexer $lexer): Primitive
    {
        /** @phpstan-ignore-next-line */
        return new static($lexer->guid());
    }

    public function getOpenAPISchema(): array
    {
        return [
            'type' => Constants::oapiString,
            'format' => 'uuid',
            'pattern' => '^'.Lexer::guid.'$',
        ];
    }
}
