<?php

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\PrimitiveType;

class Binary extends PrimitiveType
{
    protected $identifier = 'Edm.Binary';

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
        parent::set($value);

        $result = base64_decode(str_replace(['-', '_'], ['+', '/'], $value));
        if (false === $result) {
            $result = null;
        }

        $this->value = $this->maybeNull(null === $value ? null : $result);

        return $this;
    }
}
