<?php

namespace Flat3\OData\Interfaces;

use Flat3\OData\EntitySet;

interface FromEntitySetInterface
{
    public function fromEntitySet(EntitySet $entitySet);
}