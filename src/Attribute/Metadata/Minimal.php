<?php

namespace Flat3\OData\Attribute\Metadata;

use Flat3\OData\Attribute\Metadata;

final class Minimal extends Metadata
{
    public const name = 'minimal';
    public const required = ['nextLink', 'count', 'context', 'etag', 'deltaLink'];
}
