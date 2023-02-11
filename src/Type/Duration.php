<?php

declare(strict_types=1);

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\PathSegment\OpenAPI;
use Flat3\Lodata\Primitive;
use Flat3\Lodata\Property;

/**
 * Duration
 * @package Flat3\Lodata\Type
 */
class Duration extends Primitive
{
    const identifier = 'Edm.Duration';

    /** @var ?float $value */
    protected $value;

    public function toUrl(): string
    {
        if (null === $this->value) {
            return Constants::null;
        }

        return sprintf("'%s'", $this::numberToDuration($this->value));
    }

    public static function numberToDuration($seconds): string
    {
        $r = 'P';

        $d = floor($seconds / 86400);
        $r .= $d > 0 ? $d.'D' : '';
        $seconds -= ($d * 86400);

        $r .= 'T';

        $h = floor($seconds / 3600);
        $r .= $h > 0 ? $h.'H' : '';
        $seconds -= ($h * 3600);

        $m = floor($seconds / 60);
        $r .= $m > 0 ? $m.'M' : '';
        $seconds -= ($m * 60);
        $r .= $seconds >= 0 ? $seconds.'S' : '';

        return $r;
    }

    public function set($value): self
    {
        $this->value = null === $value ? null : (is_numeric($value) ? (double) $value : $this::durationToNumber($value));

        return $this;
    }

    public static function durationToNumber(string $duration): ?float
    {
        $result = preg_match(
            '@^(?P<neg>-?)P((?P<d>[0-9]+)D)?(T((?P<h>[0-9]+)H)?((?P<m>[0-9]+)M)?((?P<s>[0-9]+([.][0-9]+)?)S)?)?$@',
            $duration,
            $matches
        );

        $matches = $result === 1 ? $matches : null;

        if (!$matches) {
            return null;
        }

        $neg = $matches['neg'] ?? '' === '-' ? -1 : 1;

        return (double) (
            $neg * (
                ((int) ($matches['d'] ?? 0)) * 86400 +
                ((int) ($matches['h'] ?? 0)) * 3600 +
                ((int) ($matches['m'] ?? 0)) * 60 +
                ((float) ($matches['s'] ?? 0)))
        );
    }

    public function toJson(): ?string
    {
        if (null === $this->value) {
            return null;
        }

        return $this::numberToDuration($this->value);
    }

    public function toMixed(): ?float
    {
        return $this->value;
    }

    public function get(): ?float
    {
        return parent::get();
    }

    public static function fromLexer(Lexer $lexer): Primitive
    {
        /** @phpstan-ignore-next-line */
        return new static($lexer->duration());
    }

    public function getOpenAPISchema(?Property $property = null): array
    {
        return OpenAPI::applyProperty($property, [
            'type' => Constants::oapiString,
            'format' => 'duration',
            'pattern' => '^'.Lexer::duration.'$',
        ]);
    }
}
