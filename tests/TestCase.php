<?php

namespace Flat3\Lodata\Tests;

use Exception;
use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Exception\Protocol\NotFoundException;
use Flat3\Lodata\Exception\Protocol\ProtocolException;
use Flat3\Lodata\ServiceProvider;
use Flat3\Lodata\Tests\Data\TestModels;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Mockery\Expectation;
use PDOException;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Spatie\Snapshots\MatchesSnapshots;
use stdClass;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use MatchesSnapshots;
    use RefreshDatabase;
    use TestModels;
    use WithoutMiddleware;

    /** @var Expectation $gateMock */
    protected $gateMock;

    protected $uuid;

    protected $databaseSnapshot;

    public function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();
        $this->gateMock = Gate::shouldReceive('denies');
        $this->gateMock->andReturnFalse();
        $this->getDisk();

        config(['lodata.readonly' => false]);

        $this->uuid = 0;

        Str::createUuidsUsing(function (): string {
            return Uuid::fromInteger($this->uuid++);
        });
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

    protected function getDisk(): Filesystem
    {
        return Storage::fake(config('lodata.disk'));
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

    protected function assertODataError(Request $request, int $code): TestResponse
    {
        $response = $this->req($request);
        $this->assertEquals($code, $response->getStatusCode());
        $content = $this->responseContent($response);

        if (!$content) {
            $this->assertEquals(Response::HTTP_NO_CONTENT, $code);
            return $response;
        }

        if (Response::HTTP_NO_CONTENT === $code) {
            $this->assertEmpty($content);
        }

        $this->assertMatchesSnapshot($content, new JsonDriver());

        return $response;
    }

    protected function assertUnauthorizedHttpException(Request $request): ProtocolException
    {
        return $this->assertRequestExceptionSnapshot($request, UnauthorizedHttpException::class);
    }

    public function urlToReq(string $url): Request
    {
        $request = Request::factory();

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
     * @return stdClass
     */
    public function jsonResponse(TestResponse $response): stdClass
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
        $this->assertMatchesSnapshot($content, new JsonDriver());
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
        $this->assertMatchesSnapshot($this->responseContent($response), new JsonDriver());
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
}
