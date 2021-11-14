<?php

declare(strict_types=1);

namespace Flat3\Lodata\Attributes;

use Attribute;
use Flat3\Lodata\Operation;

#[Attribute(Attribute::TARGET_METHOD)]
class LodataAction extends LodataOperation
{
    const operationType = Operation::action;
}