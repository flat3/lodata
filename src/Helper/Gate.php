<?php

namespace Flat3\Lodata\Helper;

use Flat3\Lodata\Exception\Protocol\ForbiddenException;
use Flat3\Lodata\ServiceProvider;

class Gate
{
    public static function check($gate, ...$arguments): void
    {
        if (ServiceProvider::usingPreview()) {
            return;
        }

        if (\Illuminate\Support\Facades\Gate::denies('lodata.'.$gate, $arguments)) {
            throw new ForbiddenException('forbidden', 'This request is not permitted');
        }
    }
}