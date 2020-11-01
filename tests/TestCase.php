<?php

namespace Flat3\Lodata\Tests;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Exception\Protocol\AcceptedException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\ForbiddenException;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Exception\Protocol\MethodNotAllowedException;
use Flat3\Lodata\Exception\Protocol\NoContentException;
use Flat3\Lodata\Exception\Protocol\NotAcceptableException;
use Flat3\Lodata\Exception\Protocol\NotFoundException;
use Flat3\Lodata\Exception\Protocol\NotImplementedException;
use Flat3\Lodata\Exception\Protocol\PreconditionFailedException;
use Flat3\Lodata\Exception\Protocol\ProtocolException;
use Flat3\Lodata\ServiceProvider;
use Flat3\Lodata\Tests\Data\TestModels;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Mockery\Expectation;
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

    public function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();
        $this->gateMock = Gate::shouldReceive('denies');
        $this->gateMock->andReturnFalse();
        $this->getDisk();

        config(['lodata.readonly' => false]);
    }

    protected function getPackageProviders($app)
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
        }
    }

    protected function assertNotAcceptable(Request $request): ProtocolException
    {
        return $this->assertRequestExceptionSnapshot($request, NotAcceptableException::class);
    }

    protected function assertMethodNotAllowed(Request $request): ProtocolException
    {
        return $this->assertRequestExceptionSnapshot($request, MethodNotAllowedException::class);
    }

    protected function assertNotFound(Request $request): ProtocolException
    {
        return $this->assertRequestExceptionSnapshot($request, NotFoundException::class);
    }

    protected function assertNoContent(Request $request): ProtocolException
    {
        return $this->assertRequestExceptionSnapshot($request, NoContentException::class);
    }

    protected function assertNotAuthenticated(Request $request)
    {
        try {
            $this->req($request);
        } catch (UnauthorizedHttpException $e) {
            return;
        }

        throw new RuntimeException('Failed to throw exception');
    }

    protected function assertForbidden(Request $request): ProtocolException
    {
        return $this->assertRequestExceptionSnapshot($request, ForbiddenException::class);
    }

    protected function assertPreconditionFailed(Request $request): ProtocolException
    {
        return $this->assertRequestExceptionSnapshot($request, PreconditionFailedException::class);
    }

    protected function assertNotImplemented(Request $request): ProtocolException
    {
        return $this->assertRequestExceptionSnapshot($request, NotImplementedException::class);
    }

    protected function assertInternalServerError(Request $request): ProtocolException
    {
        return $this->assertRequestExceptionSnapshot($request, InternalServerErrorException::class);
    }

    protected function assertBadRequest(Request $request): ProtocolException
    {
        return $this->assertRequestExceptionSnapshot($request, BadRequestException::class);
    }

    protected function assertAccepted(Request $request): ProtocolException
    {
        return $this->assertRequestExceptionSnapshot($request, AcceptedException::class);
    }

    public function urlToReq(string $url): Request
    {
        $request = Request::factory();

        $url = parse_url($url);
        $request->path($url['path'], false);
        parse_str($url['query'], $query);

        foreach ($query as $key => $value) {
            $request->query($key, $value);
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
        return $this->assertResponseMetadata(new TestResponse($response));
    }

    protected function assertMetadataResponse(Request $request): TestResponse
    {
        $response = $this->req($request);
        $this->assertResponseMetadata($response);
        return $response;
    }

    protected function assertJsonResponse(Request $request): TestResponse
    {
        $response = $this->req($request);
        $this->assertMatchesSnapshot($this->responseContent($response), new JsonDriver());
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

    protected function assertJsonMetadataResponse(Request $request)
    {
        $response = $this->req($request);
        $this->assertMatchesSnapshot($this->responseContent($response), new JsonDriver());
        $this->assertResponseMetadata($response);
    }

    protected function assertTextMetadataResponse(Request $request)
    {
        $response = $this->req($request);
        $this->assertMatchesTextSnapshot($this->responseContent($response));
        $this->assertResponseMetadata($response);
    }
}
