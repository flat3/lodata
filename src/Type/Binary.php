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

    public function toUrl(): string
    {
        if (null === $this->value) {
            return Constants::null;
        }

        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(stream_get_contents($this->value)));
    }

    public function toJson(): ?string
    {
        return null === $this->value ? null : base64_encode(stream_get_contents($this->value));
    }

    public function toMixed()
    {
        return $this->value;
    }

    public function set($value): self
    {
        if (null === $value) {
            $this->value = null;
            return $this;
        }

        if (is_resource($value)) {
            $this->value = $value;
            return $this;
        }

        $this->value = fopen('php://memory', 'r+');

        if (($decoded = base64_decode(str_replace(['-', '_'], ['+', '/'], (string) $value), true)) !== false) {
            fwrite($this->value, $decoded);
        } else {
            fwrite($this->value, $value);
        }

        rewind($this->value);

        return $this;
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
