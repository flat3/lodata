<?php

namespace Flat3\Lodata\Tests\Unit\Parser;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Drivers\SQLEntitySet;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Internal\ParserException;
use Flat3\Lodata\Exception\Protocol\NotImplementedException;
use Flat3\Lodata\Expression\Parser\Search;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type;
use Illuminate\Http\Request;

class SearchTest extends TestCase
{
    public function test_0()
    {
        $this->assertResult('t1',);
    }

    public function test_1()
    {
        $this->assertResult('t1 OR t2',);
    }

    public function test_2()
    {
        $this->assertResult('t1 OR t2 OR t3',);
    }

    public function test_3()
    {
        $this->assertResult('t1 OR t2 AND t3',);
    }

    public function test_4()
    {
        $this->assertResult('t1 OR t2 NOT t3 AND t4',);
    }

    public function test_5()
    {
        $this->assertResult('"a t1" OR t1',);
    }

    public function test_6()
    {
        $this->assertResult('"a \'\'t1" OR t1',);
    }

    public function test_7()
    {
        $this->assertResult('( t1 OR t2 ) AND t3',);
    }

    public function test_8()
    {
        $this->assertResult('(t1 OR (t2 AND t3))',);
    }

    public function test_9()
    {
        $this->assertResult('"t1"""',);
    }

    public function test_a()
    {
        $this->assertResult('""',);
    }

    public function assertLoopbackSet($input)
    {
        $type = new class('test') extends EntityType {
        };
        $k = new DeclaredProperty('id', Type::int32());
        $type->setKey($k);
        $entitySet = new LoopbackEntitySet('test', $type);

        $parser = new Search($entitySet);

        try {
            $tree = $parser->generateTree($input);
            $tree->compute();

            $this->assertMatchesSnapshot(trim($entitySet->searchBuffer));
        } catch (ParserException $e) {
            $this->assertMatchesSnapshot($e->getMessage());
        }
    }

    public function assertSQLSet($input)
    {
        $set = new class('test', $this->getType()) extends SQLEntitySet {
        };

        $this->assertResultSet($set, $input);
    }

    public function getType()
    {
        $entityType = new class('test') extends EntityType {
        };
        $id = new DeclaredProperty('id', Type::int32());
        $id->setFilterable(true)->setSearchable(true);
        $entityType->setKey($id);
        $title = new DeclaredProperty('title', Type::string());
        $title->setFilterable(true)->setSearchable(true);
        $entityType->addProperty($title);
        return $entityType;
    }

    public function assertResult($input)
    {
        $this->assertLoopbackSet($input);
        $this->assertSQLSet($input);
    }

    public function assertResultSet(SQLEntitySet $set, $input)
    {
        try {
            $transaction = new Transaction();
            $request = new Request();
            $request->query->set('$search', $input);
            $transaction->initialize($request);
            $query = $set->setTransaction($transaction);

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
