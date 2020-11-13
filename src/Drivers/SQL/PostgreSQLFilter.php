<?php

namespace Flat3\Lodata\Drivers\SQL;

use Flat3\Lodata\Exception\Internal\NodeHandledException;
use Flat3\Lodata\Expression\Event;
use Flat3\Lodata\Expression\Event\StartFunction;
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
     * @param  Event  $event  Filter event
     * @return bool|null
     * @throws NodeHandledException
     */
    public function pgsqlFilter(Event $event): ?bool
    {
        switch (true) {
            case $event instanceof StartFunction:
                $func = $event->getNode();

                switch (true) {
                    case $func instanceof Ceiling:
                        $this->addWhere('CEILING(');

                        return true;

                    case $func instanceof Floor:
                        $this->addWhere('FLOOR(');

                        return true;

                    case $func instanceof Round:
                        $this->addWhere('ROUND(');

                        return true;

                    case $func instanceof Day:
                        $this->addWhere("DATE_PART('DAY',");

                        return true;

                    case $func instanceof Hour:
                        $this->addWhere("DATE_PART('HOUR',");

                        return true;

                    case $func instanceof Minute:
                        $this->addWhere("DATE_PART('MINUTE',");

                        return true;

                    case $func instanceof Month:
                        $this->addWhere("DATE_PART('MONTH',");

                        return true;

                    case $func instanceof Now:
                        $this->addWhere('NOW(');

                        return true;

                    case $func instanceof Second:
                        $this->addWhere("DATE_PART('SECOND',");

                        return true;

                    case $func instanceof Year:
                        $this->addWhere("DATE_PART('ISOYEAR',");

                        return true;

                    case $func instanceof ToLower:
                        $this->addWhere('LOWER(');

                        return true;

                    case $func instanceof ToUpper:
                        $this->addWhere('UPPER(');

                        return true;

                    case $func instanceof Trim:
                        $this->addWhere('TRIM(');

                        return true;

                    case $func instanceof Concat:
                        $this->addWhere('CONCAT(');

                        return true;

                    case $func instanceof MatchesPattern:
                        $arguments = $func->getArguments();
                        list($arg1, $arg2) = $arguments;
                        $arg1->compute();
                        $this->addWhere('SIMILAR TO');
                        $arg2->compute();
                        throw new NodeHandledException();

                    case $func instanceof IndexOf:
                        $arguments = $func->getArguments();
                        list($arg1, $arg2) = $arguments;
                        $this->addWhere('POSITION(');
                        $arg1->compute();
                        $this->addWhere('IN');
                        $arg2->compute();
                        $this->addWhere(')');
                        throw new NodeHandledException();

                    case $func instanceof Length:
                        $this->addWhere('LENGTH(');

                        return true;

                    case $func instanceof Substring:
                        $this->addWhere('SUBSTR(');

                        return true;
                }
                break;
        }

        $this->sqlLambdaFilter($event);

        return false;
    }
}
