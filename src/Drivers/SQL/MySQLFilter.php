<?php

namespace Flat3\Lodata\Drivers\SQL;

use Flat3\Lodata\Exception\Internal\NodeHandledException;
use Flat3\Lodata\Expression\Event;
use Flat3\Lodata\Expression\Event\Operator;
use Flat3\Lodata\Expression\Event\StartFunction;
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
use Flat3\Lodata\Expression\Node\Func\StringCollection\Contains;
use Flat3\Lodata\Expression\Node\Func\StringCollection\EndsWith;
use Flat3\Lodata\Expression\Node\Func\StringCollection\IndexOf;
use Flat3\Lodata\Expression\Node\Func\StringCollection\Length;
use Flat3\Lodata\Expression\Node\Func\StringCollection\StartsWith;
use Flat3\Lodata\Expression\Node\Func\StringCollection\Substring;
use Flat3\Lodata\Expression\Node\Operator\Arithmetic\Div;

/**
 * MySQL Filter
 * @package Flat3\Lodata\Drivers\SQL
 */
trait MySQLFilter
{
    /**
     * MySQL-specific SQL filter generation
     * @param  Event  $event  Filter event
     * @return bool|null
     * @throws NodeHandledException
     */
    public function mysqlFilter(Event $event): ?bool
    {
        switch (true) {
            case $event instanceof Operator:
                $operator = $event->getNode();

                switch (true) {
                    case $operator instanceof Div:
                        $this->addWhere('DIV');

                        return true;
                }
                break;

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

                    case $func instanceof Date:
                        $this->addWhere('DATE(');

                        return true;

                    case $func instanceof Day:
                        $this->addWhere('DAY(');

                        return true;

                    case $func instanceof Hour:
                        $this->addWhere('HOUR(');

                        return true;

                    case $func instanceof Minute:
                        $this->addWhere('MINUTE(');

                        return true;

                    case $func instanceof Month:
                        $this->addWhere('MONTH(');

                        return true;

                    case $func instanceof Now:
                        $this->addWhere('NOW(');

                        return true;

                    case $func instanceof Second:
                        $this->addWhere('SECOND(');

                        return true;

                    case $func instanceof Time:
                        $this->addWhere('TIME(');

                        return true;

                    case $func instanceof Year:
                        $this->addWhere('YEAR(');

                        return true;

                    case $func instanceof MatchesPattern:
                        $this->addWhere('REGEXP_LIKE(');

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

                    case $func instanceof Contains:
                    case $func instanceof EndsWith:
                    case $func instanceof StartsWith:
                        $arguments = $func->getArguments();
                        list($arg1, $arg2) = $arguments;

                        $arg1->compute();
                        $this->addWhere('LIKE');
                        $value = $arg2->getValue();

                        if ($func instanceof StartsWith || $func instanceof Contains) {
                            $value .= '%';
                        }

                        if ($func instanceof EndsWith || $func instanceof Contains) {
                            $value = '%'.$value;
                        }

                        $arg2->setValue($value);
                        $arg2->compute();
                        throw new NodeHandledException();

                    case $func instanceof IndexOf:
                        $this->addWhere('INSTR(');

                        return true;

                    case $func instanceof Length:
                        $this->addWhere('LENGTH(');

                        return true;

                    case $func instanceof Substring:
                        $this->addWhere('SUBSTRING(');

                        return true;
                }
                break;
        }

        return false;
    }
}
