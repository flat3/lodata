<?php

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Primitive;

/**
 * Decimal
 * @package Flat3\Lodata\Type
 * @method static self factory($value = null, ?bool $nullable = true)
 */
class Decimal extends Numeric
{
    const identifier = 'Edm.Decimal';

    const openApiSchema = [
        'anyOf' => [
            [
                'type' => Constants::OAPI_NUMBER,
                'format' => 'decimal',
            ],
            [
                'enum' => [
                    Constants::NEG_INFINITY,
                    Constants::INFINITY,
                    Constants::NOT_A_NUMBER,
                ]
            ],
        ],
    ];

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
            return Constants::NOT_A_NUMBER;
        }

        if (is_infinite($this->value)) {
            return ($this->value < 0) ? Constants::NEG_INFINITY : Constants::INFINITY;
        }

        return $this->value;
    }

    public function toUrl(): string
    {
        if (null === $this->value) {
            return Constants::NULL;
        }

        if (is_nan($this->value)) {
            return Constants::NOT_A_NUMBER;
        }

        if (is_infinite($this->value)) {
            return ($this->value < 0) ? Constants::NEG_INFINITY : Constants::INFINITY;
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
                case Constants::INFINITY:
                    $this->value = INF;

                    return $this;

                case Constants::NEG_INFINITY:
                    $this->value = -INF;

                    return $this;

                case Constants::NOT_A_NUMBER:
                    $this->value = NAN;

                    return $this;
            }
        }

        $this->value = $this->maybeNull(null === $value ? null : (float) $value);

        return $this;
    }

    protected function getEmpty()
    {
        return 0.0;
    }

    public static function fromLexer(Lexer $lexer): Primitive
    {
        /** @phpstan-ignore-next-line */
        return new static($lexer->number());
    }
}
