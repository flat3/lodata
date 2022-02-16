<?php

declare(strict_types=1);

namespace Flat3\Lodata\Type;

use Carbon\CarbonImmutable as Carbon;
use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Primitive;

/**
 * Time Of Day
 * @package Flat3\Lodata\Type
 */
class TimeOfDay extends DateTimeOffset
{
    const identifier = 'Edm.TimeOfDay';

    const openApiSchema = [
        'type' => Constants::oapiString,
        'format' => 'time',
        'pattern' => '^'.Lexer::timeOfDay.'$',
    ];

    public const dateFormat = 'H:i:s.u';

    protected function repack(Carbon $dt): Carbon
    {
        return $dt->setDate(1970, 1, 1);
    }

    public function toScalar(): ?string
    {
        return $this->value === null ? null : $this->value->toTimeString();
    }

    public static function fromLexer(Lexer $lexer): Primitive
    {
        /** @phpstan-ignore-next-line */
        return new static($lexer->timeOfDay());
    }
}
