<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Core\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Type\String_;

/**
 * SchemaVersion
 * @package Flat3\Lodata\Annotation\Core\V1
 */
class SchemaVersion extends Annotation
{
    protected $name = 'Org.OData.Core.V1.SchemaVersion';

    public function __construct(string $schemaVersion)
    {
        $this->value = new String_($schemaVersion);
    }
}