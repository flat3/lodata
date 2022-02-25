<?php

namespace Flat3\Lodata\Tests\Parser;

use Flat3\Lodata\Controller\Request;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Drivers\SQLEntitySet;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Internal\ParserException;
use Flat3\Lodata\Exception\Protocol\ProtocolException;
use Flat3\Lodata\Expression\Parser\Filter;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\NavigationBinding;
use Flat3\Lodata\NavigationProperty;
use Flat3\Lodata\ReferentialConstraint;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type;

class FilterTest extends TestCase
{
    public function test_0()
    {
        $this->assertResult('origin eq "test"');
    }

    public function test_1()
    {
        $this->assertResult("origin eq 'test'");
    }

    public function test_2()
    {
        $this->assertResult("origin eq 'test");
    }

    public function test_3()
    {
        $this->assertResult('id eq 4');
    }

    public function test_4()
    {
        $this->assertResult('id gt 4');
    }

    public function test_5()
    {
        $this->assertResult('id lt 4');
    }

    public function test_6()
    {
        $this->assertResult('id ge 4');
    }

    public function test_7()
    {
        $this->assertResult('id le 4');
    }

    public function test_8()
    {
        $this->assertResult('id eq test');
    }

    public function test_9()
    {
        $this->assertResult("origin in ('a', 'b', 'c')");
    }

    public function test_a()
    {
        $this->assertResult("origin in ('a')");
    }

    public function test_b()
    {
        $this->assertResult('id in (4, 3)');
    }

    public function test_c()
    {
        $this->assertResult('id lt 4 and id gt 2');
    }

    public function test_d()
    {
        $this->assertResult('id lt 4 or id gt 2');
    }

    public function test_e()
    {
        $this->assertResult('id lt 4 or id lt 3 or id lt 2');
    }

    public function test_f()
    {
        $this->assertResult('id lt 4 or id lt 3 and id lt 2');
    }

    public function test_10()
    {
        $this->assertResult('id lt 4 or id in (3, 1) and id ge 2');
    }

    public function test_11()
    {
        $this->assertResult('(id lt 4 and (id ge 7 or id gt 3)');
    }

    public function test_12()
    {
        $this->assertResult('(id lt 4 a');
    }

    public function test_13()
    {
        $this->assertResult('(id lt 4 and id ge 7) or id gt 3');
    }

    public function test_14()
    {
        $this->assertResult('id lt 4 or (id gt 3 and id gt 2)');
    }

    public function test_15()
    {
        $this->assertResult('(id lt 4 and id ge 7) or (id gt 3 and id gt 2)');
    }

    public function test_16()
    {
        $this->assertResult('id add 3.14 eq 1.59');
    }

    public function test_17()
    {
        $this->assertResult('id in (1.59, 2.14)');
    }

    public function test_18()
    {
        $this->assertResult('(id add 3.14) in (1.59, 2.14) or (id gt -2.40 and id gt 4 add 5)');
    }

    public function test_19()
    {
        $this->assertResult('id add 3.14 add 5 in (1.59, 2.14)');
    }

    public function test_1a()
    {
        $this->assertResult('id add 3.14 in (1.59, 2.14)');
    }

    public function test_1b()
    {
        $this->assertResult('id add 3.14 in (1.59, 2.14) or (id gt -2.40 and id gt 4 add 5)');
    }

    public function test_1c()
    {
        $this->assertResult("not(contains(origin,'a')) and ((origin eq 'abcd') or (origin eq 'e'))");
    }

    public function test_1d()
    {
        $this->assertResult("not(origin eq 'a')");
    }

    public function test_1e()
    {
        $this->assertResult("origin eq 'b' and not(origin eq 'a')");
    }

    public function test_1f()
    {
        $this->assertResult("origin eq 'b' or not(origin eq 'a')");
    }

    public function test_20()
    {
        $this->assertResult("contains(origin, 'b')");
    }

    public function test_21()
    {
        $this->assertResult("endswith(origin, 'b')");
    }

    public function test_22()
    {
        $this->assertResult("concat(origin, 'abc') eq '123abc'");
    }

    public function test_23()
    {
        $this->assertResult("concat(origin, 'abc', 4.0) eq '123abc'");
    }

    public function test_24()
    {
        $this->assertResult("concat(origin, id) eq '123abc'");
    }

    public function test_25()
    {
        $this->assertResult("concat(origin, concat(id, 4)) eq '123abc'");
    }

    public function test_26()
    {
        $this->assertResult("indexof(origin,'abc123') eq 1");
    }

    public function test_27()
    {
        $this->assertResult('length(origin) eq 1');
    }

    public function test_28()
    {
        $this->assertResult("substring(origin,1) eq 'abc123'");
    }

    public function test_29()
    {
        $this->assertResult("substring(origin,1,4) eq 'abc123'");
    }

    public function test_2a()
    {
        $this->assertResult("matchesPattern(origin,'^A.*e$')");
    }

    public function test_2b()
    {
        $this->assertResult("tolower(origin) eq 'abc123'");
    }

    public function test_2c()
    {
        $this->assertResult("toupper(origin) eq 'abc123'");
    }

    public function test_2d()
    {
        $this->assertResult("trim(origin) eq 'abc123'");
    }

    public function test_2e()
    {
        $this->assertResult('ceiling(origin) eq 4');
    }

    public function test_2f()
    {
        $this->assertResult('floor(origin) eq 4');
    }

    public function test_30()
    {
        $this->assertResult('origin eq 4 div 3');
    }

    public function test_31()
    {
        $this->assertResult('origin eq 4 divby 3');
    }

    public function test_32()
    {
        $this->assertResult('origin eq 4 add 3');
    }

    public function test_33()
    {
        $this->assertResult('origin eq 4 sub 3');
    }

    public function test_34()
    {
        $this->assertResult('origin eq 4 mul 3');
    }

    public function test_35()
    {
        $this->assertResult('origin eq 4 mod 3');
    }

    public function test_36()
    {
        $this->assertResult('origin eq 4');
    }

    public function test_37()
    {
        $this->assertResult('origin gt 4');
    }

    public function test_38()
    {
        $this->assertResult('origin ge 4');
    }

    public function test_39()
    {
        $this->assertResult('origin in (4,3)');
    }

    public function test_3a()
    {
        $this->assertResult('origin lt 4');
    }

    public function test_3b()
    {
        $this->assertResult('origin le 4');
    }

    public function test_3c()
    {
        $this->assertResult('origin ne 4');
    }

    public function test_3d()
    {
        $this->assertResult('origin eq true');
    }

    public function test_3e()
    {
        $this->assertResult('origin eq false');
    }

    public function test_3f()
    {
        $this->assertResult('origin eq 2000-01-01');
    }

    public function test_40()
    {
        $this->assertResult('origin eq 2000-01-01T12:34:59Z');
    }

    public function test_41()
    {
        $this->assertResult('origin eq 04:11:12');
    }

    public function test_42()
    {
        $this->assertResult('origin eq 4AA33245-E2D1-470D-9433-01AAFCF0C83F');
    }

    public function test_43()
    {
        $this->assertResult('origin eq PT1M');
    }

    public function test_44()
    {
        $this->assertResult('origin eq PT36H');
    }

    public function test_45()
    {
        $this->assertResult('origin eq P10DT2H30M');
    }

    public function test_46()
    {
        $this->assertResult('round(origin) eq 4',);
    }

    public function test_47()
    {
        $this->assertResult('date(origin) eq 2001-01-01');
    }

    public function test_48()
    {
        $this->assertResult('day(origin) eq 4');
    }

    public function test_49()
    {
        $this->assertResult('hour(origin) eq 3');
    }

    public function test_4a()
    {
        $this->assertResult('minute(origin) eq 33');
    }

    public function test_4b()
    {
        $this->assertResult('month(origin) eq 11');
    }

    public function test_4c()
    {
        $this->assertResult('now() eq 10:00:00');
    }

    public function test_4d()
    {
        $this->assertResult('second(origin) eq 44');
    }

    public function test_4e()
    {
        $this->assertResult('time(origin) eq 10:00:00');
    }

    public function test_4f()
    {
        $this->assertResult('year(origin) eq 1999');
    }

    public function test_50()
    {
        $this->assertResult("endswith(origin,'a')");
    }

    public function test_51()
    {
        $this->assertResult("indexof(origin,'a') eq 1");
    }

    public function test_52()
    {
        $this->assertResult("startswith(origin,'a')");
    }

    public function test_53()
    {
        $this->assertResult('origin eq 2000-01-01T12:34:59+01:00');
    }

    public function test_54()
    {
        $this->assertResult('origin eq 2000-01-01T12:34:59-01:00');
    }

    public function test_55()
    {
        $this->assertResult("startswith(origin,'Veniam et') eq true");
    }

    public function test_56()
    {
        $this->assertResult("true eq startswith(origin,'Veniam et')");
    }

    public function test_57()
    {
        $this->assertResult("startswith(origin,'Veniam et') gt 4");
    }

    public function test_58()
    {
        $this->assertResult("4 lt startswith(origin,'Veniam et')");
    }

    public function test_59()
    {
        $this->assertResult("endswith(origin,'Veniam et') eq true and startswith(origin,'Veniam et') eq true");
    }

    public function test_5a()
    {
        $this->assertResult("endswith(origin,'Veniam et') eq true and not(startswith(origin,'Veniam et') eq true)");
    }

    public function test_5b()
    {
        $this->assertResult("airports/any(d:d/name eq 'hello')");
    }

    public function test_5c()
    {
        $this->assertResult("endswith(origin,'Veniam et') eq true and not(startswith(origin,'Veniam et') eq true)");
    }

    public function test_5d()
    {
        $this->assertResult("airports/all(d:d/name eq 'hello')");
    }

    public function test_5e()
    {
        $this->assertResult("da/all(d:d/name eq 'hello')");
    }

    public function test_5f()
    {
        $this->assertResult('origin gt (now() sub PT3M)');
    }

    public function test_60()
    {
        $this->assertResult("origin EQ 'lax'");
    }

    /**
     * @param  EntitySet|string  $entitySetClass
     */
    public function generateModel(string $entitySetClass)
    {
        $flightType = Lodata::getEntityType('flight');
        $airportType = Lodata::getEntityType('airport');

        $flightSet = Lodata::getEntitySet('flights');
        if ($flightSet) {
            Lodata::drop($flightSet->getIdentifier());
        }

        $airportSet = Lodata::getEntitySet('airports');
        if ($airportSet) {
            Lodata::drop($airportSet->getIdentifier());
        }

        if (!$flightType) {
            $flightType = (new EntityType('flight'))
                ->setKey(new DeclaredProperty('id', Type::int32()))
                ->addDeclaredProperty('origin', Type::string())
                ->addDeclaredProperty('destination', Type::string());
            Lodata::add($flightType);
        }

        if (!$airportType) {
            $airportType = (new EntityType('airport'))
                ->setKey(new DeclaredProperty('id', Type::int32()))
                ->addDeclaredProperty('name', Type::string())
                ->addProperty((new DeclaredProperty('code', Type::string()))->setSearchable());
            Lodata::add($airportType);
        }

        $originCode = new ReferentialConstraint(
            $flightType->getProperty('origin'),
            $airportType->getProperty('code')
        );

        $destinationCode = new ReferentialConstraint(
            $flightType->getProperty('destination'),
            $airportType->getProperty('code')
        );

        $flightSet = new $entitySetClass('flights', $flightType);
        $airportSet = new $entitySetClass('airports', $airportType);

        Lodata::add($flightSet);
        Lodata::add($airportSet);

        $toAirport = (new NavigationProperty($airportSet, $airportType))
            ->setCollection(true)
            ->addConstraint($originCode)
            ->addConstraint($destinationCode);

        $binding = new NavigationBinding($toAirport, $airportSet);

        $flightType->addProperty($toAirport);
        $flightSet->addNavigationBinding($binding);

        $destinationAirport = (new NavigationProperty('da', $airportType))
            ->setCollection(true)
            ->addConstraint($destinationCode);

        $binding = new NavigationBinding($destinationAirport, $airportSet);

        $flightType->addProperty($destinationAirport);
        $flightSet->addNavigationBinding($binding);
    }

    public function assertResult($input)
    {
        $this->generateModel(LoopbackEntitySet::class);
        $entitySet = clone Lodata::getEntitySet('flights');

        $parser = new Filter();
        $parser->pushEntitySet($entitySet);

        try {
            $tree = $parser->generateTree($input);
            $entitySet->commonExpression($tree);

            $this->assertMatchesSnapshot(trim($entitySet->commonBuffer));
        } catch (ParserException $e) {
            $this->assertMatchesSnapshot($e->getMessage());
        }

        $this->generateModel(MySQLEntitySet::class);
        /** @var MySQLEntitySet $entitySet */
        $entitySet = Lodata::getEntitySet('flights');
        $this->assertResultSet($entitySet, $input);

        $this->generateModel(PostgreSQLEntitySet::class);
        /** @var PostgreSQLEntitySet $entitySet */
        $entitySet = Lodata::getEntitySet('flights');
        $this->assertResultSet($entitySet, $input);

        $this->generateModel(SQLiteEntitySet::class);
        /** @var SQLiteEntitySet $entitySet */
        $entitySet = Lodata::getEntitySet('flights');
        $this->assertResultSet($entitySet, $input);

        $this->generateModel(SQLServerEntitySet::class);
        /** @var SQLServerEntitySet $entitySet */
        $entitySet = Lodata::getEntitySet('flights');
        $this->assertResultSet($entitySet, $input);
    }

    public function assertResultSet(SQLEntitySet $set, $input)
    {
        try {
            $transaction = new Transaction();
            $request = new Request(new \Illuminate\Http\Request());
            $request->query->set('$filter', $input);
            $request->query->set('$select', 'id,origin');
            $transaction->initialize($request);

            $set = clone $set;
            $query = $set->setTransaction($transaction);

            $container = $query->getResultExpression();

            $this->assertMatchesSnapshot($container->getStatement());
            $this->assertMatchesSnapshot($container->getParameters());
        } catch (ParserException|ProtocolException $exception) {
            $this->assertMatchesSnapshot($exception->getMessage());
        }
    }
}
