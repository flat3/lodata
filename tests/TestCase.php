<?php

namespace Flat3\Lodata\Tests;

use Closure;
use DOMDocument;
use Eclipxe\XmlSchemaValidator\SchemaValidator;
use Exception;
use Faker\Factory;
use Faker\Generator;
use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Exception\Protocol\NotFoundException;
use Flat3\Lodata\Exception\Protocol\ProtocolException;
use Flat3\Lodata\ServiceProvider;
use Flat3\Lodata\Tests\Data\TestModels;
use Flat3\Lodata\Tests\Data\TestOperations;
use Flat3\Lodata\Type\Guid;
use Illuminate\Contracts\Filesystem\Filesystem as FilesystemContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use League\Flysystem\Filesystem;
use Lunaweb\RedisMock\Providers\RedisMockServiceProvider;
use Mockery\Expectation;
use PDOException;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use VirtualFileSystem\FileSystem as VirtualFileSystem;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use MatchesSnapshots;
    use RefreshDatabase;
    use TestModels;
    use TestOperations;
    use WithoutMiddleware;

    /** @var Expectation $gateMock */
    protected $gateMock;

    /** @var int $uuid */
    protected $uuid;

    /** @var string $databaseSnapshot */
    protected $databaseSnapshot;

    /** @var Generator $faker */
    protected $faker;

    public function getEnvironmentSetUp($app)
    {
        config(['database.redis.client' => 'mock']);
        config(['filesystems.disks.testing' => ['driver' => 'vfs']]);
        config(['lodata.readonly' => false]);
        config(['lodata.disk' => 'testing']);

        $app->register(RedisMockServiceProvider::class);

        $this->gateMock = Gate::shouldReceive('denies');
        $this->gateMock->andReturnFalse();

        Str::createUuidsUsing(function (): string {
            return Uuid::fromInteger($this->uuid++);
        });

        Storage::extend('vfs', function () {
            return new Filesystem(new VfsAdapter(new VirtualFileSystem()), ['url' => 'http://odata.files']);
        });

        $this->faker = Factory::create();
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();
        $this->uuid = 0;
        $this->faker->seed(1234);
        $this->trackQueries();
    }

    public function incrementUuid()
    {
        $this->uuid++;
    }

    protected function getPackageProviders($app): array
    {
        return [
            ServiceProvider::class,
        ];
    }

    protected function getDisk(): FilesystemContract
    {
        return Storage::disk(config('lodata.disk'));
    }

    protected function assertRequestExceptionSnapshot(Request $request, string $exceptionClass): ProtocolException
    {
        try {
            $this->req($request);
            throw new RuntimeException('Failed to throw exception');
        } catch (ProtocolException $e) {
            if (!$e instanceof $exceptionClass) {
                throw new RuntimeException('Incorrect exception thrown: '.get_class($e));
            }

            $this->assertMatchesObjectSnapshot($e->serialize());
            return $e;
        } catch (Exception $e) {
            if (!$e instanceof $exceptionClass) {
                throw new RuntimeException('Incorrect exception thrown: '.get_class($e));
            }

            return new InternalServerErrorException();
        }
    }

    protected function assertNotFound(Request $request): TestResponse
    {
        return $this->assertODataError($request, Response::HTTP_NOT_FOUND);
    }

    protected function assertFound(Request $request): TestResponse
    {
        return $this->assertODataError($request, Response::HTTP_FOUND);
    }

    protected function assertForbidden(Request $request): TestResponse
    {
        return $this->assertODataError($request, Response::HTTP_FORBIDDEN);
    }

    protected function assertAccepted(Request $request): TestResponse
    {
        return $this->assertODataError($request, Response::HTTP_ACCEPTED);
    }

    protected function assertBadRequest(Request $request): TestResponse
    {
        return $this->assertODataError($request, Response::HTTP_BAD_REQUEST);
    }

    protected function assertNotImplemented(Request $request): TestResponse
    {
        return $this->assertODataError($request, Response::HTTP_NOT_IMPLEMENTED);
    }

    protected function assertPreconditionFailed(Request $request): TestResponse
    {
        return $this->assertODataError($request, Response::HTTP_PRECONDITION_FAILED);
    }

    protected function assertNotModified(Request $request): TestResponse
    {
        return $this->assertODataError($request, Response::HTTP_NOT_MODIFIED);
    }

    protected function assertNotAcceptable(Request $request): TestResponse
    {
        return $this->assertODataError($request, Response::HTTP_NOT_ACCEPTABLE);
    }

    protected function assertInternalServerError(Request $request): TestResponse
    {
        return $this->assertODataError($request, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    protected function assertNotFoundException(Request $request): ProtocolException
    {
        return $this->assertRequestExceptionSnapshot($request, NotFoundException::class);
    }

    protected function assertNoContent(Request $request): TestResponse
    {
        return $this->assertODataError($request, Response::HTTP_NO_CONTENT);
    }

    protected function assertMethodNotAllowed(Request $request): TestResponse
    {
        return $this->assertODataError($request, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    protected function assertConflict(Request $request): TestResponse
    {
        return $this->assertODataError($request, Response::HTTP_CONFLICT);
    }

    protected function assertODataError(Request $request, int $code): TestResponse
    {
        $emptyCodes = [Response::HTTP_NO_CONTENT, Response::HTTP_FOUND, Response::HTTP_NOT_MODIFIED];
        $response = $this->req($request);
        $content = $this->responseContent($response);
        $this->assertEquals($code, $response->getStatusCode());

        if (!$content) {
            $this->assertContains($code, $emptyCodes);
            return $response;
        }

        if (in_array($code, $emptyCodes)) {
            $this->assertEmpty($content);
        }

        $this->assertMatchesSnapshot($content, new StreamingJsonDriver());

        return $response;
    }

    protected function assertUnauthorizedHttpException(Request $request): ProtocolException
    {
        return $this->assertRequestExceptionSnapshot($request, UnauthorizedHttpException::class);
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

    /**
     * @param  TestResponse  $response
     * @return object
     */
    public function jsonResponse(TestResponse $response): object
    {
        return json_decode($this->responseContent($response));
    }

    private function responseContent(TestResponse $response)
    {
        return $response->baseResponse instanceof StreamedResponse ? $response->streamedContent() : $response->getContent();
    }

    public function req(Request $request): TestResponse
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

    protected function assertXmlResponse(Request $request)
    {
        $response = $this->req($request);
        $this->assertMatchesXmlSnapshot($this->responseContent($response));
        $this->assertResponseMetadata($response);
    }

    protected function assertMetadataDocuments()
    {
        $response = $this->req(
            (new Request)
                ->path('/$metadata')
                ->xml()
        );

        $xml = $this->responseContent($response);
        $this->assertMatchesXmlSnapshot($xml);
        $this->assertResponseMetadata($response);

        $document = new DOMDocument();
        $document->loadXML($xml);
        $validator = new SchemaValidator($document);
        $schemas = $validator->buildSchemas();
        $schemas->create('http://docs.oasis-open.org/odata/ns/edm', __DIR__.'/schemas/edm.xsd');
        $schemas->create('http://docs.oasis-open.org/odata/ns/edmx', __DIR__.'/schemas/edmx.xsd');
        $validator->validateWithSchemas($schemas);

        $this->assertJsonResponse(
            (new Request)
                ->path('/$metadata')
        );

        $this->assertJsonResponse(
            (new Request)
                ->path('/openapi.json')
        );
    }

    protected function assertResponseMetadata(TestResponse $response)
    {
        $this->assertMatchesSnapshot([
            'headers' => array_diff_key($response->baseResponse->headers->all(), array_flip(['date'])),
            'status' => $response->baseResponse->getStatusCode(),
        ]);
    }

    protected function assertStoredResponseMetadata(string $metadata)
    {
        $metadata = json_decode($metadata, true);
        $response = new Response();
        $response->headers->replace($metadata['headers']);
        $response->setStatusCode($metadata['status']);
        $this->assertResponseMetadata(new TestResponse($response));
    }

    protected function assertMetadataResponse(Request $request): TestResponse
    {
        $response = $this->req($request);
        $this->assertResponseMetadata($response);
        return $response;
    }

    protected function assertJsonResponse(Request $request, int $statusCode = Response::HTTP_OK): TestResponse
    {
        $response = $this->req($request);
        $content = $this->responseContent($response);
        $this->assertEquals($statusCode, $response->getStatusCode());
        $this->assertMatchesSnapshot($content, new StreamingJsonDriver());
        return $response;
    }

    protected function assertHtmlResponse(Request $request): TestResponse
    {
        $response = $this->req($request);
        $this->assertMatchesHtmlSnapshot($this->responseContent($response));
        return $response;
    }

    protected function assertTextResponse(Request $request): TestResponse
    {
        $response = $this->req($request);
        $this->assertMatchesTextSnapshot($this->responseContent($response));
        return $response;
    }

    protected function assertProtocolExceptionSnapshot(ProtocolException $e)
    {
        $this->assertMatchesSnapshot($e->serialize());
    }

    protected function assertJsonMetadataResponse(Request $request): TestResponse
    {
        $response = $this->req($request);
        $this->assertMatchesSnapshot($this->responseContent($response), new StreamingJsonDriver());
        $this->assertResponseMetadata($response);
        return $response;
    }

    protected function assertTextMetadataResponse(Request $request)
    {
        $response = $this->req($request);
        $this->assertMatchesTextSnapshot($this->responseContent($response));
        $this->assertResponseMetadata($response);
    }

    protected function snapshotDatabase(): array
    {
        $db = [];

        foreach (DB::connection()->getDoctrineSchemaManager()->listTableNames() as $table) {
            if ($table === 'migrations') {
                continue;
            }

            $db[$table] = DB::table($table)->select('*')->get();
        }

        return $db;
    }

    protected function captureDatabaseState()
    {
        $this->databaseSnapshot = $this->snapshotDatabase();
    }

    protected function assertDatabaseMatchesCapturedState()
    {
        $this->assertEquals($this->databaseSnapshot, $this->snapshotDatabase());
    }

    protected function assertDatabaseSnapshot()
    {
        $this->assertMatchesObjectSnapshot($this->snapshotDatabase());
    }

    protected function assertNoTransactionsInProgress()
    {
        try {
            $this->getConnection('testing')->beginTransaction();
            $this->getConnection('testing')->rollBack();
        } catch (PDOException $e) {
            $this->fail('A transaction was in progress');
        }
    }

    protected function assertGuid(string $expected, string $actual)
    {
        $this->assertSame($expected, Guid::binaryToString($actual));
    }

    // https://github.com/mattiasgeniar/phpunit-query-count-assertions/blob/master/src/AssertsQueryCounts.php
    protected function assertNoQueriesExecuted(Closure $closure = null): void
    {
        if ($closure) {
            self::trackQueries();

            $closure();
        }

        $this->assertQueryCountMatches(0);

        if ($closure) {
            DB::flushQueryLog();
        }
    }

    protected function assertQueryCountMatches(int $count, Closure $closure = null): void
    {
        if ($closure) {
            self::trackQueries();

            $closure();
        }

        $this->assertEquals($count, self::getQueryCount());

        if ($closure) {
            DB::flushQueryLog();
        }
    }

    protected function assertQueryCountLessThan(int $count, Closure $closure = null): void
    {
        if ($closure) {
            self::trackQueries();

            $closure();
        }

        $this->assertLessThan($count, self::getQueryCount());

        if ($closure) {
            DB::flushQueryLog();
        }
    }

    protected function assertQueryCountGreaterThan(int $count, Closure $closure = null): void
    {
        if ($closure) {
            self::trackQueries();

            $closure();
        }

        $this->assertGreaterThan($count, self::getQueryCount());

        if ($closure) {
            DB::flushQueryLog();
        }
    }

    protected static function trackQueries(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
    }

    protected static function getQueriesExecuted(): array
    {
        return DB::getQueryLog();
    }

    protected static function getQueryCount(): int
    {
        return count(self::getQueriesExecuted());
    }
}
