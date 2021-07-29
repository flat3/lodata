<?php

declare(strict_types=1);

namespace Flat3\Lodata\Type;

use Carbon\CarbonImmutable as Carbon;
use Exception;
use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Primitive;

/**
 * Date Time Offset
 * @package Flat3\Lodata\Type
 * @method static self factory($value = null, ?bool $nullable = true)
 */
class DateTimeOffset extends Primitive
{
    const identifier = 'Edm.DateTimeOffset';

    const openApiSchema = [
        'type' => Constants::oapiString,
        'format' => 'date-time',
        'pattern' => '^'.Lexer::dateTimeOffset.'$',
    ];

    public const dateFormat = 'c';

    /** @var ?Carbon $value */
    protected $value;

    public function set($value): self
    {
        if (is_bool($value)) {
            $value = $this->getEmpty();
        }

        if ($value instanceof Carbon) {
            $this->value = $value;

            return $this;
        }

        try {
            $decodedValue = rawurldecode((string) $value);

            if (is_numeric($decodedValue)) {
                $decodedValue = '@'.$decodedValue;
            }

            $dt = new Carbon($decodedValue);
            $this->value = $this->maybeNull(null === $value ? null : $this->repack($dt));
        } catch (Exception $e) {
            $this->value = $this->getEmpty();
        }

        return $this;
    }

    protected function getEmpty(): Carbon
    {
        return (new Carbon())->setTimestamp(0);
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

    public static function fromLexer(Lexer $lexer): Primitive
    {
        /** @phpstan-ignore-next-line */
        return new static($lexer->datetimeoffset());
    }
}
