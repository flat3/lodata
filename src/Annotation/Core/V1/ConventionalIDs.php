<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Core\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Type\Boolean;

/**
 * Conventional IDs
 * @package Flat3\Lodata\Annotation\Core\V1
 */
class ConventionalIDs extends Annotation
{
    protected $name = 'Org.OData.Core.V1.ConventionalIDs';

    public function __construct()
    {
        $this->value = new Boolean(true);
    }
}