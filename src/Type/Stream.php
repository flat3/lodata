<?php

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Primitive;

/**
 * Stream
 * @package Flat3\Lodata\Type
 * @method static self factory($value = null, ?bool $nullable = true)
 */
class Stream extends Primitive
{
    const identifier = 'Edm.Stream';

    /** @var ?string $value */
    protected $value;

    public function toUrl(): string
    {
        if (null === $this->value) {
            return Constants::NULL;
        }

        return sprintf("'%s'", $this->value);
    }

    public function toJson()
    {
        if (null === $this->value) {
            return null;
        }

        return (string) $this->value;
    }

    public function set($value): self
    {
        $this->value = $this->maybeNull(null === $value ? null : $value);

        return $this;
    }
}
