<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Helpers\StreamingJsonMatches;

use SebastianBergmann\Comparator\ComparisonFailure;

class StreamingJsonMatches80 extends \Flat3\Lodata\Tests\Helpers\StreamingJsonMatches
{
    public function fail($other, string $description, ComparisonFailure $comparisonFailure = null): void
    {
        $this->fail($other, $description, $comparisonFailure);
    }
}