<?php

namespace Flat3\OData\Type;

use Flat3\OData\Expression\Lexer;
use Flat3\OData\Helper\Constants;
use Flat3\OData\PrimitiveType;

class Duration extends PrimitiveType
{
    protected $name = 'Edm.Duration';

    /** @var ?double $value */
    protected $value;

    public function toUrl(): string
    {
        if (null === $this->value) {
            return Constants::NULL;
        }

        return sprintf("'%s'", $this->numberToDuration($this->value));
    }

    protected function numberToDuration($seconds): string
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

    public function set($value): void
    {
        $this->value = $this->maybeNull(null === $value ? null : (is_numeric($value) ? (double) $value : $this->duration_to_number($value)));
    }

    protected function duration_to_number(string $duration): ?float
    {
        $matches = Lexer::patternMatch(Lexer::ISO8601_DURATION, $duration);

        if (!$matches) {
            return null;
        }

        return (double) (
            ((int) $matches['d'] ?? 0) * 86400 +
            ((int) $matches['h'] ?? 0) * 3600 +
            ((int) $matches['m'] ?? 0) * 60 +
            ((float) $matches['s'] ?? 0)
        );
    }

    public function toJson(): ?string
    {
        if (null === $this->value) {
            return null;
        }

        return $this->numberToDuration($this->value);
    }

    protected function getEmpty()
    {
        return 0;
    }
}
