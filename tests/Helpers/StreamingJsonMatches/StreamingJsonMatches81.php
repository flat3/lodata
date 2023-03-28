<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Helpers\StreamingJsonMatches;

use Exception;
use Flat3\Lodata\Tests\Helpers\StreamingJsonMatches;
use SebastianBergmann\Comparator\ComparisonFailure;

class StreamingJsonMatches81 extends StreamingJsonMatches
{
    public function fail($other, string $description, ComparisonFailure $comparisonFailure = null): never
    {
        $this->_fail($other, $description, $comparisonFailure);

        throw new Exception();
    }
}
