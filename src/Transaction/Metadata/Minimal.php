<?php

namespace Flat3\Lodata\Transaction\Metadata;

use Flat3\Lodata\Transaction\Metadata;

final class Minimal extends Metadata
{
    public const name = 'minimal';
    protected $requiredProperties = ['nextLink', 'count', 'context', 'etag', 'deltaLink'];
}
