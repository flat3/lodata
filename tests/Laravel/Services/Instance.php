<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Laravel\Services;

use Flat3\Lodata\Attributes\LodataFunction;

class Instance
{
    public $a = 'b';

    #[LodataFunction]
    public function insarg(): string
    {
        return $this->a;
    }
}