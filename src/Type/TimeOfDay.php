<?php

declare(strict_types=1);

namespace Flat3\Lodata\Type;

use Carbon\CarbonImmutable as Carbon;
use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\PathSegment\OpenAPI;
use Flat3\Lodata\Primitive;
use Flat3\Lodata\Property;

/**
 * Time Of Day
 * @package Flat3\Lodata\Type
 */
class TimeOfDay extends DateTimeOffset
{
    const identifier = 'Edm.TimeOfDay';

    public const dateFormat = 'H:i:s.u';

    protected function repack(Carbon $dt): Carbon
    {
        return $dt->setDate(1970, 1, 1);
    }

    public function toMixed(): ?string
    {
        return null === $this->value ? null : $this->value->toTimeString();
    }

    public static function fromLexer(Lexer $lexer): Primitive
    {
        /** @phpstan-ignore-next-line */
        return new static($lexer->timeOfDay());
    }

    public function getOpenAPISchema(?Property $property = null): array
    {
        return OpenAPI::applyProperty($property, [
            'type' => Constants::oapiString,
            'format' => 'time',
            'pattern' => '^'.Lexer::timeOfDay.'$',
        ]);
    }
}
