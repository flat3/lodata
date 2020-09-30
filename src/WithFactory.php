<?php

namespace Flat3\OData;

trait WithFactory
{
    public static function factory(...$args): self
    {
        return new static(...$args);
    }
}
