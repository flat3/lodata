<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Core\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Type\String_;

/**
 * Description
 * @package Flat3\Lodata\Annotation\Core\V1
 */
class Description extends Annotation
{
    public function __construct(string $description)
    {
        $this->identifier = new Identifier('Org.OData.Core.V1.Description');
        $this->value = new String_($description);
    }
}