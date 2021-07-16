<?php

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Primitive;

/**
 * Boolean
 * @package Flat3\Lodata\Type
 * @method static self factory($value = null, ?bool $nullable = true)
 */
class Boolean extends Primitive
{
    const identifier = 'Edm.Boolean';

    const openApiSchema = [
        'type' => Constants::OAPI_BOOLEAN,
    ];

    /** @var ?bool $value */
    protected $value;

    public function toUrl(): string
    {
        if (null === $this->value) {
            return Constants::NULL;
        }

        return $this->value ? Constants::TRUE : Constants::FALSE;
    }

    public function toJson(): ?bool
    {
        return $this->value;
    }

    public function set($value): self
    {
        if (is_bool($value)) {
            $this->value = $value;

            return $this;
        }

        if (Constants::TRUE === $value) {
            $this->value = true;

            return $this;
        }

        if (Constants::FALSE === $value) {
            $this->value = false;

            return $this;
        }

        $this->value = $this->maybeNull(null === $value ? null : (bool) $value);

        return $this;
    }

    protected function getEmpty(): bool
    {
        return false;
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
}
