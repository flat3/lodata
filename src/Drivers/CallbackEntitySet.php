<?php

namespace Flat3\OData\Drivers;

use Flat3\OData\EntitySet;

class CallbackEntitySet extends EntitySet
{
    protected $callback;

    public function setCallback(callable $callback): self
    {
        $this->callback = $callback;
        return $this;
    }

    protected function query(): array
    {
        return call_user_func($this->callback);
    }
}