<?php

declare(strict_types=1);

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\PathSegment\OpenAPI;
use Flat3\Lodata\Primitive;
use Flat3\Lodata\Property;

/**
 * Boolean
 * @package Flat3\Lodata\Type
 */
class Boolean extends Primitive
{
    const identifier = 'Edm.Boolean';

    /** @var ?bool $value */
    protected $value;

    public function toUrl(): string
    {
        if (null === $this->value) {
            return Constants::null;
        }

        return $this->value ? Constants::true : Constants::false;
    }

    public function toJson(): ?bool
    {
        return $this->value;
    }

    public function toMixed(): ?bool
    {
        return $this->value;
    }

    public function set($value): self
    {
        if (is_bool($value)) {
            $this->value = $value;

            return $this;
        }

        if (Constants::true === $value) {
            $this->value = true;

            return $this;
        }

        if (Constants::false === $value) {
            $this->value = false;

            return $this;
        }

        $this->value = null === $value ? null : (bool) $value;

        return $this;
    }

    public function get(): ?bool
    {
        return parent::get();
    }

    public static function fromLexer(Lexer $lexer): Primitive
    {
        /** @phpstan-ignore-next-line */
        return new static($lexer->boolean());
    }

    public static function false(): self
    {
        return new self(false);
    }

    public static function true(): self
    {
        return new self(true);
    }

    public function getOpenAPISchema(?Property $property = null): array
    {
        return OpenAPI::applyProperty($property, [
            'type' => Constants::oapiBoolean,
        ]);
    }
}
