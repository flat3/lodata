<?php

declare(strict_types=1);

namespace Flat3\Lodata\Type;

use Flat3\Lodata\ComplexType;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Helper\Identifier;

class Untyped extends ComplexType
{
    const identifier = 'Edm.Untyped';

    public function __construct()
    {
        parent::__construct($this::identifier);
    }

    public function getIdentifier(): Identifier
    {
        return new Identifier($this::identifier);
    }

    public function getOpenAPISchema(): array
    {
        return [
            'type' => Constants::oapiObject,
            'additionalProperties' => true,
        ];
    }
}