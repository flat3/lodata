<?php

declare(strict_types=1);

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\Helper\Identifier;

/**
 * Service Interface
 * @package Flat3\Lodata\Interfaces
 */
interface ServiceInterface extends NameInterface
{
    /**
     * Get the OData kind of this service
     * @return string
     */
    public function getKind(): string;

    /**
     * Get the fully qualified identifier of this service
     * @return Identifier
     */
    public function getIdentifier(): Identifier;

    /**
     * Get the title of this service
     * @return string|null
     */
    public function getTitle(): ?string;

    /**
     * Set the title of this service
     * @param  string  $title  Title
     * @return mixed
     */
    public function setTitle(string $title);
}