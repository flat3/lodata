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
 * Date Time Offset
 * @package Flat3\Lodata\Type
 */
class DateTimeOffset extends Primitive
{
    const identifier = 'Edm.DateTimeOffset';

    public const dateFormat = 'c';

    /** @var ?Carbon $value */
    protected $value;

    public function set($value): self
    {
        if ($value instanceof Carbon) {
            $this->value = $value;

            return $this;
        }

        $decodedValue = rawurldecode((string) $value);

        if (is_numeric($decodedValue)) {
            $decodedValue = '@'.$decodedValue;
        }

        $dt = new Carbon($decodedValue);
        $this->value = null === $value ? null : $this->repack($dt);

        return $this;
    }

    public function get(): ?Carbon
    {
        return parent::get();
    }

    protected function repack(Carbon $dt): Carbon
    {
        return $dt;
    }

    public function toUrl(): string
    {
        if (null === $this->value) {
            return Constants::null;
        }

        return rawurlencode($this->value->format($this::dateFormat));
    }

    public function toJson(): ?string
    {
        if (null === $this->value) {
            return null;
        }

        return $this->value->format($this::dateFormat);
    }

    public function toMixed(): ?string
    {
        return null === $this->value ? null : $this->value->toDateTimeString();
    }

    public static function fromLexer(Lexer $lexer): Primitive
    {
        /** @phpstan-ignore-next-line */
        return new static($lexer->datetimeoffset());
    }

    public function getOpenAPISchema(?Property $property = null): array
    {
        return OpenAPI::applyProperty($property, [
            'type' => Constants::oapiString,
            'format' => 'date-time',
            'pattern' => '^'.Lexer::dateTimeOffset.'$',
        ]);
    }
}
