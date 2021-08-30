<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers\SQL;

use Flat3\Lodata\Exception\Internal\NodeHandledException;
use Flat3\Lodata\Expression\Node;
use Flat3\Lodata\Expression\Node\Func\Arithmetic\Ceiling;
use Flat3\Lodata\Expression\Node\Func\Arithmetic\Floor;
use Flat3\Lodata\Expression\Node\Func\Arithmetic\Round;
use Flat3\Lodata\Expression\Node\Func\DateTime\Date;
use Flat3\Lodata\Expression\Node\Func\DateTime\Day;
use Flat3\Lodata\Expression\Node\Func\DateTime\Hour;
use Flat3\Lodata\Expression\Node\Func\DateTime\Minute;
use Flat3\Lodata\Expression\Node\Func\DateTime\Month;
use Flat3\Lodata\Expression\Node\Func\DateTime\Now;
use Flat3\Lodata\Expression\Node\Func\DateTime\Second;
use Flat3\Lodata\Expression\Node\Func\DateTime\Time;
use Flat3\Lodata\Expression\Node\Func\DateTime\Year;
use Flat3\Lodata\Expression\Node\Func\String\MatchesPattern;
use Flat3\Lodata\Expression\Node\Func\String\ToLower;
use Flat3\Lodata\Expression\Node\Func\String\ToUpper;
use Flat3\Lodata\Expression\Node\Func\String\Trim;
use Flat3\Lodata\Expression\Node\Func\StringCollection\Concat;
use Flat3\Lodata\Expression\Node\Func\StringCollection\IndexOf;
use Flat3\Lodata\Expression\Node\Func\StringCollection\Length;
use Flat3\Lodata\Expression\Node\Func\StringCollection\Substring;
use Flat3\Lodata\Expression\Node\Operator\Arithmetic\Div;

/**
 * MySQL Filter
 * @package Flat3\Lodata\Drivers\SQL
 */
trait MySQLFilter
{
    use SQLLambda;

    /**
     * MySQL-specific SQL filter generation
     * @param  Node  $node  Node
     * @return bool|null
     * @throws NodeHandledException
     */
    public function mysqlFilter(Node $node): ?bool
    {
        switch (true) {
            case $node instanceof Div:
                $this->addWhere('DIV');

                return true;

            case $node instanceof Ceiling:
                $this->addWhere('CEILING(');

                return true;

            case $node instanceof Floor:
                $this->addWhere('FLOOR(');

                return true;

            case $node instanceof Round:
                $this->addWhere('ROUND(');

                return true;

            case $node instanceof Date:
                $this->addWhere('DATE(');

                return true;

            case $node instanceof Day:
                $this->addWhere('DAY(');

                return true;

            case $node instanceof Hour:
                $this->addWhere('HOUR(');

                return true;

            case $node instanceof Minute:
                $this->addWhere('MINUTE(');

                return true;

            case $node instanceof Month:
                $this->addWhere('MONTH(');

                return true;

            case $node instanceof Now:
                $this->addWhere('NOW(');

                return true;

            case $node instanceof Second:
                $this->addWhere('SECOND(');

                return true;

            case $node instanceof Time:
                $this->addWhere('TIME(');

                return true;

            case $node instanceof Year:
                $this->addWhere('YEAR(');

                return true;

            case $node instanceof MatchesPattern:
                $this->addWhere('REGEXP_LIKE(');

                return true;

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
                $this->addWhere('INSTR(');

                return true;

            case $node instanceof Length:
                $this->addWhere('LENGTH(');

                return true;

            case $node instanceof Substring:
                $this->addWhere('SUBSTRING(');

                return true;
        }

        $this->sqlLambdaFilter($node);

        return false;
    }
}
