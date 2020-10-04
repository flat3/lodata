<?php

namespace Flat3\OData\Transaction\Metadata;

use Flat3\OData\Transaction\Metadata;

final class Minimal extends Metadata
{
    public const name = 'minimal';
    public const required = ['nextLink', 'count', 'context', 'etag', 'deltaLink'];
}
