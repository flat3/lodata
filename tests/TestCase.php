<?php

namespace Flat3\OData\Tests;

use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Exception\Protocol\NoContentException;
use Flat3\OData\Exception\Protocol\NotAcceptableException;
use Flat3\OData\Exception\Protocol\NotFoundException;
use Flat3\OData\Exception\Protocol\PreconditionFailedException;
use Flat3\OData\ServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use MatchesSnapshots;
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();
    }

    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // perform environment setup
    }

    protected function assertNotAcceptable(Request $request)
    {
        $this->expectException(NotAcceptableException::class);
        $this->req($request);
    }

    protected function assertMethodNotAllowed(Request $request)
    {
        $this->expectException(MethodNotAllowedHttpException::class);
        $this->req($request);
    }

    protected function assertNotFound(Request $request)
    {
        $this->expectException(NotFoundException::class);
        $this->req($request);
    }

    protected function assertNoContent(Request $request)
    {
        $this->expectException(NoContentException::class);
        $this->req($request);
    }

    protected function assertPreconditionFailed(Request $request)
    {
        $this->expectException(PreconditionFailedException::class);
        $this->req($request);
    }

    public function req(Request $request)
    {
        return $this->call(
            $request->method,
            'odata'.$request->uri(),
            [],
            [],
            [],
            $this->transformHeadersToServerVars($request->headers)
        );
    }

    protected function assertBadRequest(Request $request)
    {
        $this->expectException(BadRequestException::class);
        $this->req($request);
    }

    protected function assertXmlResponse(Request $request)
    {
        $response = $this->req($request);
        $this->assertMatchesXmlSnapshot($response->streamedContent());
        $this->assertResponseMetadata($response);
    }

    protected function assertResponseMetadata(TestResponse $response)
    {
        $this->assertMatchesSnapshot([
            'headers' => array_diff_key($response->baseResponse->headers->all(), array_flip(['date'])),
            'status' => $response->baseResponse->getStatusCode(),
        ]);
    }

    protected function assertMetadataResponse(Request $request)
    {
        $response = $this->req($request);
        $this->assertResponseMetadata($response);
    }

    protected function assertJsonResponse(Request $request)
    {
        $response = $this->req($request);
        $this->assertMatchesSnapshot($response->streamedContent(), new JsonDriver());
    }

    protected function assertJsonMetadataResponse(Request $request)
    {
        $response = $this->req($request);
        $this->assertMatchesSnapshot($response->streamedContent(), new JsonDriver());
        $this->assertResponseMetadata($response);
    }

    protected function assertTextResponse(Request $request)
    {
        $response = $this->req($request);
        $this->assertMatchesTextSnapshot($response->streamedContent());
        $this->assertResponseMetadata($response);
    }
}
