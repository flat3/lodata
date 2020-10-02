<?php

namespace Flat3\OData\EntitySet;

use Flat3\OData\EntitySet;

class Callback extends EntitySet
{
    protected $callback;

    public function setCallback(callable $callback): self
    {
        $this->callback = $callback;
        return $this;
    }

    protected function generate(): array
    {
        return call_user_func($this->callback);
    }
}