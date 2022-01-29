<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Laravel\Models;

use Flat3\Lodata\Attributes\LodataFunction;
use Flat3\Lodata\Interfaces\RepositoryInterface;

class Repository implements RepositoryInterface
{
    public function getClass(): string
    {
        return Airport::class;
    }

    #[LodataFunction (bind: "airport")]
    public function code(Airport $airport, ?string $suffix): string
    {
        return $airport->code.($suffix ?: '');
    }
}