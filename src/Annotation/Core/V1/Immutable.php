<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Core\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Type\Boolean;

class Immutable extends Annotation
{
    protected $name = 'Org.OData.Core.V1.Immutable';

    public function __construct(bool $isImmutable = true)
    {
        $this->value = new Boolean($isImmutable);
    }
}