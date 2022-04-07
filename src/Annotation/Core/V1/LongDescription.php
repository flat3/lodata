<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Core\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Type\String_;

/**
 * LongDescription
 * @package Flat3\Lodata\Annotation\Core\V1
 */
class LongDescription extends Annotation
{
    public function __construct(string $longDescription)
    {
        $this->identifier = new Identifier('Org.OData.Core.V1.LongDescription');
        $this->value = new String_($longDescription);
    }
}