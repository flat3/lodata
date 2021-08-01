<?php

declare(strict_types=1);

namespace Flat3\Lodata\Traits;

/**
 * Has Title
 * @package Flat3\Lodata\Traits
 */
trait HasTitle
{
    /**
     * The title
     * @var string $title
     */
    protected $title;

    /**
     * Get the title
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Set the title
     * @param  string  $title  Title
     * @return $this
     */
    public function setTitle(string $title)
    {
        $this->title = $title;

        return $this;
    }
}