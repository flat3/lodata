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
use Flat3\Lodata\Expression\Parser\Compute;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type;

class ComputeTest extends TestCase
{
    public function test_00()
    {
        $this->assertResult('origin as comp');
    }

    public function test_01()
    {
        $this->assertResult("concat(origin, 'world') as comp");
    }

    public function test_02()
    {
        $this->assertResult("concat(origin, 'world') as comp1, 1 add 2 as comp2");
    }

    public function test_03()
    {
        $this->assertResult("false as comp3");
    }

    /**
     * @param  EntitySet|string  $entitySetClass
     */
    public function generateModel(string $entitySetClass)
    {
        $flightType = (new EntityType('flight'))
            ->setKey(new DeclaredProperty('id', Type::int32()))
            ->addDeclaredProperty('origin', Type::string())
            ->addDeclaredProperty('destination', Type::string());

        $flightSet = new $entitySetClass('flights', $flightType);

        Lodata::add($flightType);
        Lodata::add($flightSet);
    }

    public function assertResult($compute)
    {
        $this->generateModel(LoopbackEntitySet::class);
        /** @var LoopbackEntitySet $entitySet */
        $entitySet = clone Lodata::getEntitySet('flights');

        $computeParser = new Compute();
        $computeParser->pushEntitySet($entitySet);

        $computeBuffers = [];

        $computeOption = new \Flat3\Lodata\Transaction\Option\Compute();
        $computeOption->setValue($compute);

        try {
            $computedProperties = $computeOption->getProperties();
            foreach ($computedProperties as $computedProperty) {
                $tree = $computeParser->generateTree($computedProperty->getExpression());
                $entitySet->commonExpression($tree);

                $computeBuffers[] = sprintf(
                    '%s as %s',
                    trim($entitySet->commonBuffer),
                    $computedProperty->getName()
                );
                $entitySet->commonBuffer = '';
            }

            $this->assertMatchesSnapshot(join(', ', array_filter($computeBuffers)));
        } catch (ParserException $e) {
            $this->assertMatchesSnapshot($e->getMessage());
        }

        $this->generateModel(MySQLEntitySet::class);
        /** @var MySQLEntitySet $entitySet */
        $entitySet = Lodata::getEntitySet('flights');
        $this->assertResultSet($entitySet, $compute);

        $this->generateModel(PostgreSQLEntitySet::class);
        /** @var PostgreSQLEntitySet $entitySet */
        $entitySet = Lodata::getEntitySet('flights');
        $this->assertResultSet($entitySet, $compute);

        $this->generateModel(SQLiteEntitySet::class);
        /** @var SQLiteEntitySet $entitySet */
        $entitySet = Lodata::getEntitySet('flights');
        $this->assertResultSet($entitySet, $compute);

        $this->generateModel(SQLServerEntitySet::class);
        /** @var SQLServerEntitySet $entitySet */
        $entitySet = Lodata::getEntitySet('flights');
        $this->assertResultSet($entitySet, $compute);
    }

    public function assertResultSet(SQLEntitySet $set, $input)
    {
        try {
            $transaction = new Transaction();
            $request = new Request(new \Illuminate\Http\Request());
            $request->query->set('$compute', $input);
            $transaction->initialize($request);

            $query = $set->setTransaction($transaction);

            $container = $query->getResultExpression();

            $this->assertMatchesSnapshot($container->getStatement());
            $this->assertMatchesSnapshot($container->getParameters());
        } catch (ParserException|ProtocolException $exception) {
            $this->assertMatchesSnapshot($exception->getMessage());
        }
    }
}
