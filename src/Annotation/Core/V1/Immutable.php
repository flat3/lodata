<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Core\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Type\Boolean;

class Immutable extends Annotation
{
    public function __construct(bool $isImmutable = true)
    {
        $this->identifier = new Identifier('Org.OData.Core.V1.Immutable');
        $this->value = new Boolean($isImmutable);
    }
}