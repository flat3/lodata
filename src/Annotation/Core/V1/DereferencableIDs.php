<?php

namespace Flat3\Lodata\Annotation\Core\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Type\Boolean;

/**
 * Dereferencable IDs
 * @package Flat3\Lodata\Annotation\Core\V1
 */
class DereferencableIDs extends Annotation
{
    protected $name = 'Org.OData.Core.V1.DereferencableIDs';

    public function __construct()
    {
        $this->value = new Boolean(true);
    }
}