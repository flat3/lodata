<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Core\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Type\Boolean;

class Computed extends Annotation
{
    public function __construct(bool $isComputed = true)
    {
        $this->identifier = new Identifier('Org.OData.Core.V1.Computed');
        $this->value = new Boolean($isComputed);
    }
}