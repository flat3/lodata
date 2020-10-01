<?php

namespace Flat3\OData\Traits;

trait HasFactory
{
    public static function factory(...$args): self
    {
        return new static(...$args);
    }
}
