<?php

declare(strict_types=1);

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\PathSegment\OpenAPI;
use Flat3\Lodata\Primitive;
use Flat3\Lodata\Property;

/**
 * Binary
 * @package Flat3\Lodata\Type
 */
class Binary extends Primitive
{
    const identifier = 'Edm.Binary';

    /** @var ?string $value */
    protected $value;

    public function toUrl(): string
    {
        if (null === $this->value) {
            return Constants::null;
        }

        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($this->value));
    }

    public function toJson(): ?string
    {
        return null === $this->value ? null : base64_encode($this->value);
    }

    public function toMixed(): ?string
    {
        return $this->value;
    }

    public function set($value): self
    {
        $result = base64_decode(str_replace(['-', '_'], ['+', '/'], (string) $value));
        if (false === $result) {
            $result = null;
        }

        $this->value = null === $value ? null : $result;

        return $this;
    }

    public function get(): ?string
    {
        return parent::get();
    }

    public static function fromLexer(Lexer $lexer): Primitive
    {
        /** @phpstan-ignore-next-line */
        return new static($lexer->base64());
    }

    public function getOpenAPISchema(?Property $property = null): array
    {
        return OpenAPI::applyProperty($property, [
            'type' => Constants::oapiString,
            'format' => 'base64url',
            'pattern' => '^'.Lexer::base64.'$',
        ]);
    }
}
