<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Parser;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\EnumerationType;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\NavigationBinding;
use Flat3\Lodata\NavigationProperty;
use Flat3\Lodata\ReferentialConstraint;
use Flat3\Lodata\Tests\Parser\Handlers\LoopbackEntitySet;
use Flat3\Lodata\Tests\Parser\Handlers\MongoEntitySet;
use Flat3\Lodata\Tests\Parser\Handlers\MySQLEntitySet;
use Flat3\Lodata\Tests\Parser\Handlers\PostgreSQLEntitySet;
use Flat3\Lodata\Tests\Parser\Handlers\SQLiteEntitySet;
use Flat3\Lodata\Tests\Parser\Handlers\SQLServerEntitySet;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Transaction\Option\Compute;
use Flat3\Lodata\Type;
use RuntimeException;

abstract class Expression extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if ($airports = Lodata::getEntitySet('airports')) {
            Lodata::drop($airports);
        }

        if ($flights = Lodata::getEntitySet('flights')) {
            Lodata::drop($flights);
        }
    }

    protected function assertFilter(string $expression)
    {
        $priority = new EnumerationType('Priorities');
        $priority->setIsFlags();
        $priority[] = 'high';
        $priority[] = 'medium';
        $priority[] = 'low';
        Lodata::add($priority);

        $type = (new EntityType('flight'))
            ->setKey(new DeclaredProperty('id', Type::int32()))
            ->addDeclaredProperty('origin', Type::string())
            ->addDeclaredProperty('priority', $priority);

        (new LoopbackEntitySet($this, $type))->assertFilterExpression($expression);
        (new MySQLEntitySet($this, $type))->assertFilterExpression($expression);
        (new PostgreSQLEntitySet($this, $type))->assertFilterExpression($expression);
        (new SQLiteEntitySet($this, $type))->assertFilterExpression($expression);
        (new SQLServerEntitySet($this, $type))->assertFilterExpression($expression);
        (new MongoEntitySet($this, $type))->assertFilterExpression($expression);
    }

    protected function assertLambda(string $expression)
    {
        $flightType = (new EntityType('flight'))
            ->setKey(new DeclaredProperty('id', Type::int32()))
            ->addDeclaredProperty('origin', Type::string())
            ->addDeclaredProperty('destination', Type::string());

        $loopback = new LoopbackEntitySet($this, $flightType);
        $mysql = new MySQLEntitySet($this, $flightType);
        $postgres = new PostgreSQLEntitySet($this, $flightType);
        $sqlite = new SQLiteEntitySet($this, $flightType);
        $sqlserver = new SQLServerEntitySet($this, $flightType);

        $airportType = (new EntityType('airport'))
            ->setKey(new DeclaredProperty('id', Type::int32()))
            ->addDeclaredProperty('name', Type::string())
            ->addProperty((new DeclaredProperty('code', Type::string()))->setSearchable());

        $originCode = new ReferentialConstraint(
            $flightType->getProperty('origin'),
            $airportType->getProperty('code')
        );

        $destinationCode = new ReferentialConstraint(
            $flightType->getProperty('destination'),
            $airportType->getProperty('code')
        );

        $loopbackAirport = new class($this, $airportType, 'airports') extends LoopbackEntitySet {
        };

        $mysqlAirport = new class($this, $airportType, 'airports') extends MySQLEntitySet {
        };

        $postgresAirport = new class($this, $airportType, 'airports') extends PostgreSQLEntitySet {
        };

        $sqliteAirport = new class($this, $airportType, 'airports') extends SQLiteEntitySet {
        };

        $sqlserverAirport = new class($this, $airportType, 'airports') extends SQLServerEntitySet {
        };

        foreach ([$loopback, $mysql, $postgres, $sqlite, $sqlserver,] as $set) {
            $airportSet = null;

            switch (true) {
                case $set instanceof LoopbackEntitySet:
                    $airportSet = Lodata::add($loopbackAirport);
                    break;

                case $set instanceof MySQLEntitySet:
                    $airportSet = Lodata::add($mysqlAirport);
                    break;

                case $set instanceof PostgreSQLEntitySet:
                    $airportSet = Lodata::add($postgresAirport);
                    break;

                case $set instanceof SQLiteEntitySet:
                    $airportSet = Lodata::add($sqliteAirport);
                    break;

                case $set instanceof SQLServerEntitySet:
                    $airportSet = Lodata::add($sqlserverAirport);
                    break;
            }

            $toAirport = (new NavigationProperty($airportSet, $airportType))
                ->setCollection(true)
                ->addConstraint($originCode)
                ->addConstraint($destinationCode);

            $binding1 = new NavigationBinding($toAirport, $airportSet);

            $flightType->addProperty($toAirport);

            $destinationAirport = (new NavigationProperty('da', $airportType))
                ->setCollection(true)
                ->addConstraint($destinationCode);

            $binding2 = new NavigationBinding($destinationAirport, $airportSet);

            $flightType->addProperty($destinationAirport);

            $set->addNavigationBinding($binding1);
            $set->addNavigationBinding($binding2);
            $set->assertFilterExpression($expression);
        }
    }

    protected function assertSearch(string $expression)
    {
        $type = (new EntityType('flight'))
            ->setKey(new DeclaredProperty('id', Type::int32()))
            ->addProperty((new DeclaredProperty('from', Type::string()))->setSearchable())
            ->addProperty((new DeclaredProperty('to', Type::string()))->setSearchable());

        (new LoopbackEntitySet($this, $type))->assertSearchExpression($expression);
        (new MySQLEntitySet($this, $type))->assertSearchExpression($expression);
        (new PostgreSQLEntitySet($this, $type))->assertSearchExpression($expression);
        (new SQLiteEntitySet($this, $type))->assertSearchExpression($expression);
        (new SQLServerEntitySet($this, $type))->assertSearchExpression($expression);
    }

    protected function assertCompute(string $expression)
    {
        $type = (new EntityType('flight'))
            ->setKey(new DeclaredProperty('id', Type::int32()))
            ->addDeclaredProperty('origin', Type::string());

        $option = new Compute();
        $option->setValue($expression);

        foreach ($option->getProperties() as $property) {
            (new LoopbackEntitySet($this, $type))->assertComputeExpression($property->getExpression());
            (new MySQLEntitySet($this, $type))->assertComputeExpression($property->getExpression());
            (new PostgreSQLEntitySet($this, $type))->assertComputeExpression($property->getExpression());
            (new SQLiteEntitySet($this, $type))->assertComputeExpression($property->getExpression());
            (new SQLServerEntitySet($this, $type))->assertComputeExpression($property->getExpression());
        }
    }

    public function evaluate(string $expression)
    {
        return true;
    }

    public function assertTrueExpression($expression): void
    {
        $this->assertTrue($this->evaluate($expression));
    }

    public function assertFalseExpression($expression): void
    {
        $this->assertFalse($this->evaluate($expression));
    }

    public function assertNullExpression($expression): void
    {
        $this->assertNull($this->evaluate($expression));
    }

    public function assertSameExpression($expected, $expression): void
    {
        $this->assertSame($expected, $this->evaluate($expression));
    }

    public function assertBadExpression($expression): void
    {
        $this->expectException(BadRequestException::class);
        $this->evaluate($expression);
        throw new RuntimeException('Failed to throw exception');
    }

    public function assertMatchesExpressionSnapshot(string $input, string $output, ?array $parameters = []): void
    {
        $snapshot = sprintf("expression: %s\nresult: %s\n", $input, $output);

        if ($parameters) {
            $snapshot .= sprintf("parameters: %s\n", join(',', $parameters));
        }

        $this->assertMatchesTextSnapshot($snapshot);
    }
}
