<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Core\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Type\Boolean;

/**
 * Dereferencable IDs
 * @package Flat3\Lodata\Annotation\Core\V1
 */
class DereferencableIDs extends Annotation
{
    public function __construct()
    {
        $this->identifier = new Identifier('Org.OData.Core.V1.DereferencableIDs');
        $this->value = new Boolean(true);
    }
}