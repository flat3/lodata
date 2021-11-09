<?php

declare(strict_types=1);

namespace Flat3\Lodata\Helper;

use Flat3\Lodata\Annotation;

/**
 * Annotations
 * @package Flat3\Lodata\Helper
 */
class Annotations extends ObjectArray
{
    protected $types = [Annotation::class];

    public function supportsCount(): bool
    {
        return $this->firstByClass(Annotation\Capabilities\V1\CountRestrictions::class)->isCountable();
    }

    public function supportsDelete(): bool
    {
        return $this->firstByClass(Annotation\Capabilities\V1\DeleteRestrictions::class)->isDeletable();
    }

    public function supportsExpand(): bool
    {
        return $this->firstByClass(Annotation\Capabilities\V1\ExpandRestrictions::class)->isExpandable();
    }

    public function supportsFilter(): bool
    {
        return $this->firstByClass(Annotation\Capabilities\V1\FilterRestrictions::class)->isFilterable();
    }

    public function supportsInsert(): bool
    {
        return $this->firstByClass(Annotation\Capabilities\V1\InsertRestrictions::class)->isInsertable();
    }

    public function supportsRead(): bool
    {
        return $this->firstByClass(Annotation\Capabilities\V1\ReadRestrictions::class)->isReadable();
    }

    public function supportsSearch(): bool
    {
        return $this->firstByClass(Annotation\Capabilities\V1\SearchRestrictions::class)->isSearchable();
    }

    public function supportsSort(): bool
    {
        return $this->firstByClass(Annotation\Capabilities\V1\SortRestrictions::class)->isSortable();
    }

    public function supportsTop(): bool
    {
        return $this->firstByClass(Annotation\Capabilities\V1\TopSupported::class)->isSupported();
    }

    public function supportsUpdate(): bool
    {
        return $this->firstByClass(Annotation\Capabilities\V1\UpdateRestrictions::class)->isUpdatable();
    }
}