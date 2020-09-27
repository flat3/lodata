<?php

namespace Flat3\OData\Operation\Function_;

use Flat3\OData\Operation\Function_;
use Flat3\OData\Type;

class Callback extends Function_
{
    /** @var callable $callback */
    protected $callback;

    public function setCallback(callable $callback): self
    {
        $this->callback = $callback;

        return $this;
    }

    public function invoke(array $args): Type
    {
        return call_user_func_array($this->callback, $args);
    }
}