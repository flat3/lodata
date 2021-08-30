<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers\SQL;

use Flat3\Lodata\Exception\Internal\NodeHandledException;
use Flat3\Lodata\Expression\Node;
use Flat3\Lodata\Expression\Node\Func\Arithmetic\Ceiling;
use Flat3\Lodata\Expression\Node\Func\Arithmetic\Floor;
use Flat3\Lodata\Expression\Node\Func\Arithmetic\Round;
use Flat3\Lodata\Expression\Node\Func\DateTime\Day;
use Flat3\Lodata\Expression\Node\Func\DateTime\Hour;
use Flat3\Lodata\Expression\Node\Func\DateTime\Minute;
use Flat3\Lodata\Expression\Node\Func\DateTime\Month;
use Flat3\Lodata\Expression\Node\Func\DateTime\Now;
use Flat3\Lodata\Expression\Node\Func\DateTime\Second;
use Flat3\Lodata\Expression\Node\Func\DateTime\Year;
use Flat3\Lodata\Expression\Node\Func\String\MatchesPattern;
use Flat3\Lodata\Expression\Node\Func\String\ToLower;
use Flat3\Lodata\Expression\Node\Func\String\ToUpper;
use Flat3\Lodata\Expression\Node\Func\String\Trim;
use Flat3\Lodata\Expression\Node\Func\StringCollection\Concat;
use Flat3\Lodata\Expression\Node\Func\StringCollection\IndexOf;
use Flat3\Lodata\Expression\Node\Func\StringCollection\Length;
use Flat3\Lodata\Expression\Node\Func\StringCollection\Substring;

/**
 * SQLServer Filter
 * @package Flat3\Lodata\Drivers\SQL
 */
trait SQLServerFilter
{
    use SQLLambda;

    /**
     * Microsoft SQL Server-specific SQL filter generation
     * @param  Node  $node
     * @return bool|null
     */
    public function sqlsrvFilter(Node $node): ?bool
    {
        switch (true) {
            case $node instanceof Ceiling:
                $this->addWhere('CEILING(');

                return true;

            case $node instanceof Floor:
                $this->addWhere('FLOOR(');

                return true;

            case $node instanceof Round:
                $this->addWhere('ROUND(');

                return true;

            case $node instanceof Day:
                $this->addWhere('DATEPART(day, ');

                return true;

            case $node instanceof Hour:
                $this->addWhere('DATEPART(hour, ');

                return true;

            case $node instanceof Minute:
                $this->addWhere('DATEPART(minute, ');

                return true;

            case $node instanceof Month:
                $this->addWhere('DATEPART(month, ');

                return true;

            case $node instanceof Now:
                $this->addWhere('CURRENT_TIMESTAMP(');

                return true;

            case $node instanceof Second:
                $this->addWhere('DATEPART(second, ');

                return true;

            case $node instanceof Year:
                $this->addWhere('DATEPART(year, ');

                return true;

            case $node instanceof MatchesPattern:
                $arguments = $node->getArguments();
                list($arg1, $arg2) = $arguments;
                $arg1->compute();
                $this->addWhere('LIKE');
                $arg2->compute();
                throw new NodeHandledException();

            case $node instanceof ToLower:
                $this->addWhere('LOWER(');

                return true;

            case $node instanceof ToUpper:
                $this->addWhere('UPPER(');

                return true;

            case $node instanceof Trim:
                $this->addWhere('TRIM(');

                return true;

            case $node instanceof Concat:
                $this->addWhere('CONCAT(');

                return true;

            case $node instanceof IndexOf:
                $this->addWhere('CHARINDEX(');

                return true;

            case $node instanceof Length:
                $this->addWhere('LEN(');

                return true;

            case $node instanceof Substring:
                $this->addWhere('SUBSTRING(');

                return true;
        }

        $this->sqlLambdaFilter($node);

        return false;
    }
}
