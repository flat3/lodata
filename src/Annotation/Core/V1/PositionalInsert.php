<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Core\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Type\Boolean;

/**
 * Positional insert
 * @package Flat3\Lodata\Annotation\Core\V1
 */
class PositionalInsert extends Annotation
{
    protected $name = 'Org.OData.Core.V1.PositionalInsert';

    public function __construct()
    {
        $this->value = new Boolean(true);
    }
}