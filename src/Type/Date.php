<?php

declare(strict_types=1);

namespace Flat3\Lodata\Type;

use Carbon\CarbonImmutable as Carbon;
use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Primitive;

/**
 * Date
 * @package Flat3\Lodata\Type
 * @method static self factory($value = null, ?bool $nullable = true)
 */
class Date extends DateTimeOffset
{
    const identifier = 'Edm.Date';

    const openApiSchema = [
        'type' => Constants::OAPI_STRING,
        'format' => 'date',
        'pattern' => '^'.Lexer::DATE.'$',
    ];

    public const DATE_FORMAT = 'Y-m-d';

    protected function repack(Carbon $dt): Carbon
    {
        return $dt->setTime(0, 0, 0, 0);
    }

    public static function fromLexer(Lexer $lexer): Primitive
    {
        /** @phpstan-ignore-next-line */
        return new static($lexer->date());
    }
}
