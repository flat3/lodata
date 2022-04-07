<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Core\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Type\Boolean;

/**
 * Positional insert
 * @package Flat3\Lodata\Annotation\Core\V1
 */
class PositionalInsert extends Annotation
{
    public function __construct()
    {
        $this->identifier = new Identifier('Org.OData.Core.V1.PositionalInsert');
        $this->value = new Boolean(true);
    }
}