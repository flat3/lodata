<?php

namespace Flat3\Lodata\Tests;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\DynamicProperty;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Interfaces\EntitySet\QueryInterface;
use Flat3\Lodata\Operation;
use Flat3\Lodata\ServiceProvider;
use Flat3\Lodata\Singleton;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\Helpers\TestFilesystemAdapter;
use Flat3\Lodata\Tests\Helpers\UseCollectionAssertions;
use Flat3\Lodata\Tests\Helpers\UseDatabaseAssertions;
use Flat3\Lodata\Tests\Helpers\UseODataAssertions;
use Flat3\Lodata\Tests\Helpers\UseSnapshots;
use Flat3\Lodata\Type;
use Flat3\Lodata\Type\Decimal;
use Flat3\Lodata\Type\Int32;
use Generator;
use Illuminate\Contracts\Filesystem\Filesystem as FilesystemContract;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Lunaweb\RedisMock\Providers\RedisMockServiceProvider;
use Mockery\Expectation;
use Ramsey\Uuid\Uuid;
use ReflectionClass;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use MatchesSnapshots;
    use WithoutMiddleware;
    use UseCollectionAssertions;
    use UseDatabaseAssertions;
    use UseODataAssertions;
    use UseSnapshots;

    /** @var Expectation $gateMock */
    protected $gateMock;

    /** @var int $uuid */
    protected $uuid;

    /** @var FakerGenerator $faker */
    protected $faker;

    protected $migrations = __DIR__.'/Laravel/migrations/airline';

    protected $entitySet = 'passengers';
    protected $entitySetPath = null;
    protected $entityId = 1;
    protected $entityPath = null;
    protected $missingEntityId = 99;
    protected $etag = 'W/""';
    protected $escapedEntityId;

    protected $airportEntitySet = 'airports';
    protected $airportEntitySetPath = null;

    protected $flightEntitySet = 'flights';
    protected $flightEntitySetPath = null;

    protected $countryEntitySet = 'countries';
    protected $countryEntitySetPath = null;

    protected $petEntitySet = 'pets';
    protected $petEntitySetPath = null;

    public function getEnvironmentSetUp($app)
    {
        config([
            'database.redis.client' => 'mock',
            'filesystems.disks.testing' => ['driver' => 'vfs'],
            'lodata.readonly' => false,
            'lodata.disk' => 'testing',
            'lodata.streaming' => false,
            'lodata.pagination.max' => null,
            'lodata.pagination.default' => 200,
        ]);

        $app->register(RedisMockServiceProvider::class);

        $this->gateMock = Gate::shouldReceive('denies');
        $this->gateMock->andReturnFalse();

        Str::createUuidsUsing(function (): string {
            return Uuid::fromInteger($this->uuid++);
        });

        TestFilesystemAdapter::bind();

        $this->faker = Factory::create();
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();
        $this->uuid = 0;
        $this->faker->seed(1234);
        $this->trackQueries();
        $this->setUpDatabaseSnapshots();

        // @phpstan-ignore-next-line
        Redis::flushdb();

        (new Filesystem)->cleanDirectory(TestFilesystemAdapter::root());

        $this->setUpDriver();

        $this->entitySetPath = '/'.$this->entitySet;
        $this->airportEntitySetPath = '/'.$this->airportEntitySet;
        $this->flightEntitySetPath = '/'.$this->flightEntitySet;
        $this->countryEntitySetPath = '/'.$this->countryEntitySet;
        $this->entityPath = $this->entitySetPath.'/'.$this->entityId;
        $this->escapedEntityId = $this->entityId;

        if (is_string($this->escapedEntityId)) {
            $this->escapedEntityId = "'{$this->escapedEntityId}'";
        }
    }

    public function tearDown(): void
    {
        $this->tearDownDriver();
        parent::tearDown();
    }

    protected function getSnapshotId(): string
    {
        $id = sprintf(
            "%s__%s__%s",
            (new ReflectionClass($this))->getShortName(),
            $this->getName(),
            $this->snapshotIncrementor
        );

        if ($this->driverSpecificSnapshots) {
            $id .= '__'.$this->getConnection()->getDriverName();
        }

        return $id;
    }

    protected function getPackageProviders($app): array
    {
        return [
            ServiceProvider::class,
        ];
    }

    protected function setUpDriver()
    {
    }

    protected function tearDownDriver()
    {
    }

    protected function markTestSkippedForDriver($driver)
    {
        $driver = is_array($driver) ? $driver : [$driver];

        if (in_array($this->getConnection()->getDriverName(), $driver)) {
            $this->markTestSkipped();
        }
    }

    protected function getDisk(): FilesystemContract
    {
        return Storage::disk(config('lodata.disk'));
    }

    public function urlToReq(string $url): Request
    {
        $request = (new Request);

        $url = parse_url($url);
        $request->path($url['path'], false);

        if (array_key_exists('query', $url)) {
            parse_str($url['query'], $query);

            foreach ($query as $key => $value) {
                $request->query($key, $value);
            }
        }

        return $request;
    }

    protected function getResponseBody(TestResponse $response): object
    {
        return json_decode($this->getResponseContent($response));
    }

    protected function getResponseContent(TestResponse $response)
    {
        return $response->baseResponse instanceof StreamedResponse ? $response->streamedContent() : $response->getContent();
    }

    protected function req(Request $request): TestResponse
    {
        return $this->call(
            $request->method,
            $request->uri(),
            [],
            [],
            [],
            $this->transformHeadersToServerVars($request->headers),
            $request->body,
        );
    }

    protected function addPassengerProperties(EntityType $entityType)
    {
        $entityType->addProperty((new DeclaredProperty('name', Type::string()))->setNullable(false));
        $entityType->addDeclaredProperty('age', Type::double());
        $entityType->addDeclaredProperty('dob', Type::datetimeoffset());
        $entityType->addDeclaredProperty('chips', Type::boolean());
        $entityType->addDeclaredProperty('dq', Type::date());
        $entityType->addDeclaredProperty('in_role', Type::duration());
        $entityType->addDeclaredProperty('open_time', Type::timeofday());
        $entityType->addDeclaredProperty('flight_id', Type::int64());
    }

    protected function getSeed(): array
    {
        return [
            'alpha' => [
                'name' => 'Alpha',
                'age' => 4,
                'dob' => '2000-01-01 04:04:04',
                'chips' => true,
                'dq' => '2000-01-01',
                'in_role' => 86400,
                'open_time' => '05:05:05',
                'flight_id' => 1,
            ],
            'beta' => [
                'name' => 'Beta',
                'age' => 3,
                'dob' => '2001-02-02 05:05:05',
                'chips' => false,
                'dq' => '2001-02-02',
                'in_role' => 191105.3,
            ],
            'gamma' => [
                'name' => 'Gamma',
                'age' => 2,
                'dob' => '2002-03-03 06:06:06',
                'chips' => true,
                'dq' => '2002-03-03',
                'in_role' => 347561,
                'open_time' => '07:07:07',
                'flight_id' => 1,
            ],
            'delta' => [
                'name' => 'Delta',
                'in_role' => 127,
            ],
            'epsilon' => [
                'name' => 'Epsilon',
                'age' => 2.4,
                'dob' => '2003-04-04 07:07:07',
                'dq' => '2003-04-04',
                'open_time' => '23:11:33',
                'in_role' => 888.9,
            ]
        ];
    }

    protected function getAirportSeed(): array
    {
        return [
            [
                'code' => 'lhr',
                'name' => 'Heathrow',
                'construction_date' => '1946-03-25',
                'open_time' => '09:00:00',
                'sam_datetime' => '2001-11-10T14:00:00',
                'is_big' => true,
                'country_id' => 1,
            ],
            [
                'code' => 'lax',
                'name' => 'Los Angeles',
                'construction_date' => '1930-01-01',
                'open_time' => '08:00:00',
                'sam_datetime' => '2000-11-10T14:00:00',
                'is_big' => false,
                'country_id' => 2,
            ], [
                'code' => 'sfo',
                'name' => 'San Francisco',
                'construction_date' => '1930-01-01',
                'open_time' => '15:00:00',
                'sam_datetime' => '2001-11-10T14:00:01',
                'is_big' => null,
            ], [
                'code' => 'ohr',
                'name' => "O'Hare",
                'construction_date' => '1930-01-01',
                'open_time' => '15:00:00',
                'sam_datetime' => '1999-11-10T14:00:01',
                'is_big' => true,
            ]
        ];
    }

    protected function getFlightSeed(): array
    {
        return [
            [
                'origin' => 'lhr',
                'destination' => 'lax',
                'duration' => 41100,
            ], [
                'origin' => 'sam',
                'destination' => 'rgr',
                'duration' => 2384,
            ], [
                'origin' => 'sfo',
                'destination' => 'lax',
                'duration' => 2133,
            ]
        ];
    }

    protected function getCountrySeed(): array
    {
        return [
            [
                'name' => 'England',
            ],
            [
                'name' => 'France',
            ],
        ];
    }

    protected function getPetSeed(): array
    {
        return [
            [
                'name' => 'Banana',
                'passenger_id' => 1,
            ],
            [
                'name' => 'Coconut',
                'passenger_id' => 3,
            ],
            [
                'name' => 'Dog',
            ],
        ];
    }

    protected function withSingleton()
    {
        $type = new EntityType('sType');
        $type->addProperty(new DeclaredProperty('name', Type::string()));
        $singleton = new Singleton('sInstance', $type);
        $singleton['name'] = 'Bob';

        Lodata::add($singleton);
    }

    public function withMathFunctions()
    {
        $add = new Operation\Function_('add');
        $add->setCallable(function (Int32 $a, Int32 $b): Int32 {
            return new Int32($a->get() + $b->get());
        });
        Lodata::add($add);

        $div = new Operation\Function_('div');
        $div->setCallable(function (Int32 $a, Int32 $b): Decimal {
            return new Decimal($a->get() / $b->get());
        });
        Lodata::add($div);
    }

    public function withTextModel()
    {
        Lodata::add(
            new class(
                'texts',
                Lodata::add((new EntityType('text'))
                    ->addDeclaredProperty('a', Type::string()))
            ) extends EntitySet implements QueryInterface {
                public function query(): Generator
                {
                    $entity = $this->newEntity();
                    $entity['a'] = 'a';
                    yield $entity;
                }
            });
    }

    public function withDynamicPropertyModel()
    {
        Lodata::add(
            new class(
                'example',
                Lodata::add((new EntityType('text'))
                    ->addDeclaredProperty('declared', Type::string()))
            ) extends EntitySet implements QueryInterface {
                public function query(): Generator
                {
                    $entity = $this->newEntity();
                    $entity['declared'] = 'a';
                    $pv = $entity->newPropertyValue();
                    $pv->setValue(new Int32(3));
                    $pv->setProperty(new DynamicProperty('dynamic', Type::int32()));
                    $entity->addPropertyValue($pv);
                    yield $entity;
                }
            });
    }

    protected function withModifiedPropertySourceName()
    {
        $passengerSet = Lodata::getEntitySet($this->entitySet);
        $ageProperty = $passengerSet->getType()->getProperty('age');
        $ageProperty->setName('aage');
        $passengerSet->getType()->getProperties()->reKey();
        $passengerSet->setPropertySourceName($ageProperty, 'age');
    }
}
