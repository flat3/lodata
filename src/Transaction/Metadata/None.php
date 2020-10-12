<?php

namespace Flat3\Lodata\Transaction\Metadata;

use Flat3\Lodata\Transaction\Metadata;

final class None extends Metadata
{
    public const name = 'none';
    public const required = ['nextLink', 'count'];
}
