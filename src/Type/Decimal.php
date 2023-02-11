<?php

declare(strict_types=1);

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\PathSegment\OpenAPI;
use Flat3\Lodata\Primitive;
use Flat3\Lodata\Property;

/**
 * Decimal
 * @package Flat3\Lodata\Type
 */
class Decimal extends Numeric
{
    const identifier = 'Edm.Decimal';

    /** @var ?double $value */
    protected $value;

    public function toJsonIeee754(): ?string
    {
        $value = $this->toJson();

        if (null === $value) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        return sprintf(sprintf('%%.%dF', max(15 - floor(log10($this->value)), 0)), $this->value);
    }

    public function toJson()
    {
        if (null === $this->value) {
            return null;
        }

        if (is_nan($this->value)) {
            return Constants::notANumber;
        }

        if (is_infinite($this->value)) {
            return ($this->value < 0) ? Constants::negativeInfinity : Constants::infinity;
        }

        return $this->value;
    }

    public function toMixed(): ?float
    {
        return $this->value;
    }

    public function toUrl(): string
    {
        if (null === $this->value) {
            return Constants::null;
        }

        if (is_nan($this->value)) {
            return Constants::notANumber;
        }

        if (is_infinite($this->value)) {
            return ($this->value < 0) ? Constants::negativeInfinity : Constants::infinity;
        }

        return strtolower((string) $this->value);
    }

    public function set($value): self
    {
        if (is_float($value)) {
            $this->value = $value;

            return $this;
        }

        if (is_string($value)) {
            switch ($value) {
                case Constants::infinity:
                    $this->value = INF;

                    return $this;

                case Constants::negativeInfinity:
                    $this->value = -INF;

                    return $this;

                case Constants::notANumber:
                    $this->value = NAN;

                    return $this;
            }
        }

        $this->value = null === $value ? null : (float) $value;

        return $this;
    }

    public function get(): ?float
    {
        return parent::get();
    }

    public static function fromLexer(Lexer $lexer): Primitive
    {
        /** @phpstan-ignore-next-line */
        return new static($lexer->number());
    }

    public function getOpenAPISchema(?Property $property = null): array
    {
        return [
            'anyOf' => [
                OpenAPI::applyProperty($property, [
                    'type' => Constants::oapiNumber,
                    'format' => 'decimal',
                ]),
                OpenAPI::applyProperty($property, [
                    'type' => Constants::oapiString,
                    'enum' => [
                        Constants::negativeInfinity,
                        Constants::infinity,
                        Constants::notANumber,
                    ],
                ]),
            ],
        ];
    }
}
