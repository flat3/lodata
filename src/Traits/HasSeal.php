<?php

namespace Flat3\Lodata\Traits;

trait HasSeal
{
    private $sealed = false;

    public function seal()
    {
        $this->sealed = true;
        return $this;
    }

    public function sealed(): bool
    {
        return $this->sealed;
    }

    public function clone()
    {
        return clone $this;
    }

    public function __clone()
    {
        $this->sealed = false;
    }
}
