<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Core\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Type\String_;

/**
 * SchemaVersion
 * @package Flat3\Lodata\Annotation\Core\V1
 */
class SchemaVersion extends Annotation
{
    public function __construct(string $schemaVersion)
    {
        $this->identifier = new Identifier('Org.OData.Core.V1.SchemaVersion');
        $this->value = new String_($schemaVersion);
    }
}