<?php

namespace Flat3\OData\Transaction\Metadata;

use Flat3\OData\Transaction\Metadata;

final class None extends Metadata
{
    public const name = 'none';
    public const required = ['nextLink', 'count'];
}
