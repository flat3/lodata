<?php

declare(strict_types=1);

namespace Flat3\Lodata\Type;

use ErrorException;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Primitive;

/**
 * String
 * @package Flat3\Lodata\Type
 * @method static self factory($value = null, ?bool $nullable = true)
 */
class String_ extends Primitive
{
    const identifier = 'Edm.String';

    const openApiSchema = [
        'type' => Constants::oapiString,
    ];

    /** @var ?string $value */
    protected $value;

    public function toUrl(): string
    {
        if (null === $this->value) {
            return Constants::null;
        }

        return "'".str_replace("'", "''", $this->value)."'";
    }

    public function set($value): self
    {
        try {
            $this->value = $this->maybeNull(null === $value ? null : (string) $value);
        } catch (ErrorException $e) {
            throw new InternalServerErrorException('invalid_conversion', 'Could not convert value to string');
        }

        return $this;
    }

    public function get(): ?string
    {
        return parent::get();
    }

    public function toJson(): ?string
    {
        return $this->value;
    }

    public static function fromLexer(Lexer $lexer): Primitive
    {
        /** @phpstan-ignore-next-line */
        return new static($lexer->quotedString());
    }
}
