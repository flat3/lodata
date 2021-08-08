<?php

namespace Flat3\Lodata\Type;

use Flat3\Lodata\ComplexType;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Helper\ObjectArray;

class Untyped extends ComplexType
{
    const identifier = 'Edm.Untyped';

    public function __construct()
    {
        $this->properties = new ObjectArray();
    }

    public function getIdentifier(): string
    {
        return $this::identifier;
    }

    public function toOpenAPISchema(): array
    {
        return [
            'type' => Constants::oapiObject,
            'additionalProperties' => true,
        ];
    }
}