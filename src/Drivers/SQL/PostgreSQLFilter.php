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
 * PostgreSQL Filter
 * @package Flat3\Lodata\Drivers\SQL
 */
trait PostgreSQLFilter
{
    use SQLLambda;

    /**
     * PostgreSQL-specific SQL filter generation
     * @param  Node  $node  Node
     * @return bool|null
     */
    public function pgsqlFilter(Node $node): ?bool
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
                $this->addWhere("DATE_PART('DAY',");

                return true;

            case $node instanceof Hour:
                $this->addWhere("DATE_PART('HOUR',");

                return true;

            case $node instanceof Minute:
                $this->addWhere("DATE_PART('MINUTE',");

                return true;

            case $node instanceof Month:
                $this->addWhere("DATE_PART('MONTH',");

                return true;

            case $node instanceof Now:
                $this->addWhere('NOW(');

                return true;

            case $node instanceof Second:
                $this->addWhere("DATE_PART('SECOND',");

                return true;

            case $node instanceof Year:
                $this->addWhere("DATE_PART('ISOYEAR',");

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

            case $node instanceof MatchesPattern:
                $arguments = $node->getArguments();
                list($arg1, $arg2) = $arguments;
                $arg1->compute();
                $this->addWhere('SIMILAR TO');
                $arg2->compute();
                throw new NodeHandledException();

            case $node instanceof IndexOf:
                $arguments = $node->getArguments();
                list($arg1, $arg2) = $arguments;
                $this->addWhere('POSITION(');
                $arg1->compute();
                $this->addWhere('IN');
                $arg2->compute();
                $this->addWhere(')');
                throw new NodeHandledException();

            case $node instanceof Length:
                $this->addWhere('LENGTH(');

                return true;

            case $node instanceof Substring:
                $this->addWhere('SUBSTR(');

                return true;
        }

        $this->sqlLambdaFilter($node);

        return false;
    }
}
