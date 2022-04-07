<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Core\V1;

use Flat3\Lodata\Helper\Identifier;

class ComputedDefaultValue extends Computed
{
    public function __construct(bool $hasComputedDefaultValue = true)
    {
        parent::__construct($hasComputedDefaultValue);
        $this->identifier = new Identifier('Org.OData.Core.V1.ComputedDefaultValue');
    }
}