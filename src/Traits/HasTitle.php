<?php

namespace Flat3\Lodata\Traits;

trait HasTitle
{
    protected $title;

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title)
    {
        $this->title = $title;

        return $this;
    }
}