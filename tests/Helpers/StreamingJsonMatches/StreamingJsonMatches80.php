<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Helpers\StreamingJsonMatches;

use Flat3\Lodata\Tests\Helpers\StreamingJsonMatches;
use SebastianBergmann\Comparator\ComparisonFailure;

class StreamingJsonMatches80 extends StreamingJsonMatches
{
    public function fail($other, $description, ComparisonFailure $comparisonFailure = null): void
    {
        $this->_fail($other, $description, $comparisonFailure);
    }
}
