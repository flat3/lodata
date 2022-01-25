<?php

declare(strict_types=1);

namespace Flat3\Lodata\Transaction\Option;

use Flat3\Lodata\Transaction\Option;
use Illuminate\Support\Arr;

/**
 * Filter
 * @link https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part2-url-conventions.html#sec_SystemQueryOptionfilter
 * @package Flat3\Lodata\Transaction\Option
 */
class Filter extends Option
{
    public const param = 'filter';
    protected $filterExpressions = [];

    public function addExpression(string $expression): self
    {
        $this->filterExpressions[] = $expression;

        return $this;
    }

    public function setValue(?string $value): void
    {
        $this->filterExpressions = array_filter(array_merge($this->filterExpressions, [$value]));
    }

    public function hasValue(): bool
    {
        return !!$this->filterExpressions;
    }

    public function getValue(): string
    {
        $expressions = array_filter($this->filterExpressions);

        if (count($expressions) === 1) {
            return $expressions[0];
        }

        return sprintf("(%s)", join(') and (', $expressions));
    }
}
