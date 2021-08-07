<?php

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Type;

class Untyped extends Type
{
    const identifier = 'Edm.Untyped';

    public function instance($value = null)
    {
        return new Collection($value);
    }

    public function toOpenAPISchema(): array
    {
        return [
            'type' => Constants::oapiObject,
            'additionalProperties' => true,
        ];
    }
}