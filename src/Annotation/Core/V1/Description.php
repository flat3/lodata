<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Core\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Type\String_;

/**
 * Description
 * @package Flat3\Lodata\Annotation\Core\V1
 */
class Description extends Annotation
{
    protected $name = 'Org.OData.Core.V1.Description';

    public function __construct(string $description)
    {
        $this->value = new String_($description);
    }
}