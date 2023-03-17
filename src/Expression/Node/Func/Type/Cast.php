<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Func\Type;

use Flat3\Lodata\Exception\Protocol\NotImplementedException;
use Flat3\Lodata\Expression\Node\Func;

/**
 * Cast
 * @package Flat3\Lodata\Expression\Node\Func\Type
 */
class Cast extends Func
{
    public const symbol = 'cast';
    public const precedence = 7;
    public const arguments = 2;

    public function notImplemented(): void
    {
        throw new NotImplementedException(
            'unsupported_cast',
            sprintf(
                'This entity set cannot process a cast to "%s"',
                $this->getArguments()[1]->getValue()
            )
        );
    }
}
