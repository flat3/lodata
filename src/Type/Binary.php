<?php

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Primitive;

/**
 * Binary
 * @package Flat3\Lodata\Type
 * @method static self factory($value = null, ?bool $nullable = true)
 */
class Binary extends Primitive
{
    const identifier = 'Edm.Binary';

    const openApiSchema = [
        'type' => Constants::OAPI_STRING,
        'format' => 'base64url',
        'pattern' => '^'.Lexer::BASE64.'$',
    ];

    /** @var ?string $value */
    protected $value;

    public function toUrl(): string
    {
        if (null === $this->value) {
            return Constants::NULL;
        }

        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($this->value));
    }

    public function toJson(): ?string
    {
        return null === $this->value ? null : base64_encode($this->value);
    }

    public function set($value): self
    {
        $result = base64_decode(str_replace(['-', '_'], ['+', '/'], $value));
        if (false === $result) {
            $result = null;
        }

        $this->value = $this->maybeNull(null === $value ? null : $result);

        return $this;
    }

    public static function fromLexer(Lexer $lexer): Primitive
    {
        /** @phpstan-ignore-next-line */
        return new static($lexer->base64());
    }
}
