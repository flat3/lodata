<?php

namespace Flat3\Lodata\Tests\Helpers;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Util\Json;
use SebastianBergmann\Comparator\ComparisonFailure;

abstract class StreamingJsonMatches extends Constraint
{
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function toString(): string
    {
        return sprintf(
            'matches streaming JSON string "%s"',
            $this->value
        );
    }

    protected function matches($other): bool
    {
        return $this->recode($this->value) == $this->recode($other);
    }

    protected function recode(string $string): string
    {
        return json_encode(json_decode($string));
    }

    protected function _fail($other, string $description, ComparisonFailure $comparisonFailure = null): void
    {
        if ($comparisonFailure === null) {
            $decodedOther = json_decode($other);

            if (json_last_error()) {
                parent::fail($other, $description);
            }

            $decodedValue = json_decode($this->value);

            if (json_last_error()) {
                parent::fail($other, $description);
            }

            $comparisonFailure = new ComparisonFailure(
                $decodedValue,
                $decodedOther,
                Json::prettify($this->value),
                Json::prettify($other),
                'Failed asserting that two streaming json values are equal.'
            );
        }

        parent::fail($other, $description, $comparisonFailure);
    }
}