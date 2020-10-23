<?php

namespace Flat3\Lodata\Tests\Unit\Parser;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Drivers\SQLEntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Internal\ParserException;
use Flat3\Lodata\Exception\Protocol\NotImplementedException;
use Flat3\Lodata\Expression\Parser\Filter;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type;
use Illuminate\Http\Request;

class FilterTest extends TestCase
{
    public function test_0()
    {
        $this->assertResult('title eq "test"',);
    }

    public function test_1()
    {
        $this->assertResult("title eq 'test'",);
    }

    public function test_2()
    {
        $this->assertResult("title eq 'test",);
    }

    public function test_3()
    {
        $this->assertResult('id eq 4',);
    }

    public function test_4()
    {
        $this->assertResult('id gt 4',);
    }

    public function test_5()
    {
        $this->assertResult('id lt 4',);
    }

    public function test_6()
    {
        $this->assertResult('id ge 4',);
    }

    public function test_7()
    {
        $this->assertResult('id le 4',);
    }

    public function test_8()
    {
        $this->assertResult('id eq test',);
    }

    public function test_9()
    {
        $this->assertResult("title in ('a', 'b', 'c')",);
    }

    public function test_a()
    {
        $this->assertResult("title in ('a')",);
    }

    public function test_b()
    {
        $this->assertResult('id in (4, 3)',);
    }

    public function test_c()
    {
        $this->assertResult('id lt 4 and id gt 2',);
    }

    public function test_d()
    {
        $this->assertResult('id lt 4 or id gt 2',);
    }

    public function test_e()
    {
        $this->assertResult('id lt 4 or id lt 3 or id lt 2',);
    }

    public function test_f()
    {
        $this->assertResult('id lt 4 or id lt 3 and id lt 2',);
    }

    public function test_10()
    {
        $this->assertResult('id lt 4 or id in (3, 1) and id ge 2',);
    }

    public function test_11()
    {
        $this->assertResult('(id lt 4 and (id ge 7 or id gt 3)',);
    }

    public function test_12()
    {
        $this->assertResult('(id lt 4 a',);
    }

    public function test_13()
    {
        $this->assertResult('(id lt 4 and id ge 7) or id gt 3',);
    }

    public function test_14()
    {
        $this->assertResult('id lt 4 or (id gt 3 and id gt 2)',);
    }

    public function test_15()
    {
        $this->assertResult('(id lt 4 and id ge 7) or (id gt 3 and id gt 2)',);
    }

    public function test_16()
    {
        $this->assertResult('id add 3.14 eq 1.59',);
    }

    public function test_17()
    {
        $this->assertResult('id in (1.59, 2.14)',);
    }

    public function test_18()
    {
        $this->assertResult('(id add 3.14) in (1.59, 2.14) or (id gt -2.40 and id gt 4 add 5)',);
    }

    public function test_19()
    {
        $this->assertResult('id add 3.14 add 5 in (1.59, 2.14)',);
    }

    public function test_1a()
    {
        $this->assertResult('id add 3.14 in (1.59, 2.14)',);
    }

    public function test_1b()
    {
        $this->assertResult('id add 3.14 in (1.59, 2.14) or (id gt -2.40 and id gt 4 add 5)',);
    }

    public function test_1c()
    {
        $this->assertResult("not(contains(title,'a')) and ((title eq 'abcd') or (title eq 'e'))",);
    }

    public function test_1d()
    {
        $this->assertResult("not(title eq 'a')",);
    }

    public function test_1e()
    {
        $this->assertResult("title eq 'b' and not(title eq 'a')",);
    }

    public function test_1f()
    {
        $this->assertResult("title eq 'b' or not(title eq 'a')",);
    }

    public function test_20()
    {
        $this->assertResult("contains(title, 'b')",);
    }

    public function test_21()
    {
        $this->assertResult("endswith(title, 'b')",);
    }

    public function test_22()
    {
        $this->assertResult("concat(title, 'abc') eq '123abc'",);
    }

    public function test_23()
    {
        $this->assertResult("concat(title, 'abc', 4.0) eq '123abc'",);
    }

    public function test_24()
    {
        $this->assertResult("concat(title, id) eq '123abc'",);
    }

    public function test_25()
    {
        $this->assertResult("concat(title, concat(id, 4)) eq '123abc'",);
    }

    public function test_26()
    {
        $this->assertResult("indexof(title,'abc123') eq 1",);
    }

    public function test_27()
    {
        $this->assertResult("length(title) eq 1",);
    }

    public function test_28()
    {
        $this->assertResult("substring(title,1) eq 'abc123'",);
    }

    public function test_29()
    {
        $this->assertResult("substring(title,1,4) eq 'abc123'",);
    }

    public function test_2a()
    {
        $this->assertResult("matchesPattern(title,'^A.*e$')",);
    }

    public function test_2b()
    {
        $this->assertResult("tolower(title) eq 'abc123'",);
    }

    public function test_2c()
    {
        $this->assertResult("toupper(title) eq 'abc123'",);
    }

    public function test_2d()
    {
        $this->assertResult("trim(title) eq 'abc123'",);
    }

    public function test_2e()
    {
        $this->assertResult('ceiling(title) eq 4',);
    }

    public function test_2f()
    {
        $this->assertResult('floor(title) eq 4',);
    }

    public function test_30()
    {
        $this->assertResult('title eq 4 div 3');
    }

    public function test_31()
    {
        $this->assertResult('title eq 4 divby 3');
    }

    public function test_32()
    {
        $this->assertResult('title eq 4 add 3');
    }

    public function test_33()
    {
        $this->assertResult('title eq 4 sub 3');
    }

    public function test_34()
    {
        $this->assertResult('title eq 4 mul 3');
    }

    public function test_35()
    {
        $this->assertResult('title eq 4 mod 3');
    }

    public function test_36()
    {
        $this->assertResult('title eq 4');
    }

    public function test_37()
    {
        $this->assertResult('title gt 4');
    }

    public function test_38()
    {
        $this->assertResult('title ge 4');
    }

    public function test_39()
    {
        $this->assertResult('title in (4,3)');
    }

    public function test_3a()
    {
        $this->assertResult('title lt 4');
    }

    public function test_3b()
    {
        $this->assertResult('title le 4');
    }

    public function test_3c()
    {
        $this->assertResult('title ne 4');
    }

    public function test_3d()
    {
        $this->assertResult('title eq true');
    }

    public function test_3e()
    {
        $this->assertResult('title eq false');
    }

    public function test_3f()
    {
        $this->assertResult('title eq 2000-01-01');
    }

    public function test_40()
    {
        $this->assertResult('title eq 2000-01-01T12:34:59Z+00:00');
    }

    public function test_41()
    {
        $this->assertResult('title eq 04:11:12');
    }

    public function test_42()
    {
        $this->assertResult('title eq 4AA33245-E2D1-470D-9433-01AAFCF0C83F');
    }

    public function test_43()
    {
        $this->assertResult('title eq PT1M');
    }

    public function test_44()
    {
        $this->assertResult('title eq PT36H');
    }

    public function test_45()
    {
        $this->assertResult('title eq P10DT2H30M');
    }

    public function test_46()
    {
        $this->assertResult('round(title) eq 4',);
    }

    public function test_47()
    {
        $this->assertResult('date(title) eq 2001-01-01');
    }

    public function test_48()
    {
        $this->assertResult('day(title) eq 4');
    }

    public function test_49()
    {
        $this->assertResult('hour(title) eq 3');
    }

    public function test_4a()
    {
        $this->assertResult('minute(title) eq 33');
    }

    public function test_4b()
    {
        $this->assertResult('month(title) eq 11');
    }

    public function test_4c()
    {
        $this->assertResult('now() eq 10:00:00');
    }

    public function test_4d()
    {
        $this->assertResult('second(title) eq 44');
    }

    public function test_4e()
    {
        $this->assertResult('time(title) eq 10:00:00');
    }

    public function test_4f()
    {
        $this->assertResult('year(title) eq 1999');
    }

    public function test_50()
    {
        $this->assertResult("endswith(title,'a')");
    }

    public function test_51()
    {
        $this->assertResult("indexof(title,'a') eq 1");
    }

    public function test_52()
    {
        $this->assertResult("startswith(title,'a')");
    }

    public function assertLoopbackSet($input)
    {
        $type = new class('test') extends EntityType {
        };
        $k = new DeclaredProperty('id', Type::int32());
        $type->setKey($k);
        $transaction = new Transaction();
        $entitySet = new LoopbackEntitySet('test', $type);
        $query = $entitySet->asInstance($transaction);

        $parser = new Filter($query, $transaction);
        $parser->addValidLiteral('id');
        $parser->addValidLiteral('title');

        try {
            $tree = $parser->generateTree($input);
            $tree->compute();

            $this->assertMatchesSnapshot(trim($query->filterBuffer));
        } catch (ParserException $e) {
            $this->assertMatchesSnapshot($e->getMessage());
        }
    }

    public function assertMySQLSet($input)
    {
        $set = new class('test', $this->getType()) extends SQLEntitySet {
            public function getDriver(): string
            {
                return 'mysql';
            }
        };

        $this->assertResultSet($set, $input);
    }

    public function assertSQLiteSet($input)
    {
        $set = new class('test', $this->getType()) extends SQLEntitySet {
            public function getDriver(): string
            {
                return 'sqlite';
            }
        };

        $this->assertResultSet($set, $input);
    }

    public function assertPostgreSQLSet($input)
    {
        $set = new class('test', $this->getType()) extends SQLEntitySet {
            public function getDriver(): string
            {
                return 'pgsql';
            }
        };

        $this->assertResultSet($set, $input);
    }

    public function assertSQLSrvSet($input)
    {
        $set = new class('test', $this->getType()) extends SQLEntitySet {
            public function getDriver(): string
            {
                return 'sqlsrv';
            }
        };

        $this->assertResultSet($set, $input);
    }

    public function getType()
    {
        $entityType = new class('test') extends EntityType {
        };
        $id = new DeclaredProperty('id', Type::int32());
        $id->setFilterable(true);
        $entityType->setKey($id);
        $title = new DeclaredProperty('title', Type::string());
        $title->setFilterable(true);
        $entityType->addProperty($title);
        return $entityType;
    }

    public function assertResult($input)
    {
        $this->assertLoopbackSet($input);
        $this->assertMySQLSet($input);
        $this->assertPostgreSQLSet($input);
        $this->assertSQLiteSet($input);
        $this->assertSQLSrvSet($input);
    }

    public function assertResultSet($set, $input)
    {
        try {
            $transaction = new Transaction();
            $request = new Request();
            $request->query->set('$filter', $input);
            $request->query->set('$select', 'id,title');
            $transaction->initialize($request);
            $query = $set->asInstance($transaction);

            $queryString = $query->getSetResultQueryString();
            $queryParameters = $query->getParameters();

            $this->assertMatchesSnapshot($queryString);
            $this->assertMatchesSnapshot($queryParameters);
        } catch (ParserException $exception) {
            $this->assertMatchesSnapshot($exception->getMessage());
        } catch (NotImplementedException $exception) {
            $this->assertMatchesSnapshot($exception->getMessage());
        }
    }
}
