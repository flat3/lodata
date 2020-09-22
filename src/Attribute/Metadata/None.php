<?php

namespace Flat3\OData\Attribute\Metadata;

use Flat3\OData\Attribute\Metadata;

final class None extends Metadata
{
    public const name = 'none';
    public const required = ['nextLink', 'count'];
}
