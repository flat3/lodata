<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Core\V1;

class ComputedDefaultValue extends Computed
{
    protected $name = 'Org.OData.Core.V1.ComputedDefaultValue';

    public function __construct(bool $hasComputedDefaultValue = true)
    {
        parent::__construct($hasComputedDefaultValue);
    }
}