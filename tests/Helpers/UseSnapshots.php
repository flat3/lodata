<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Helpers;

use DOMDocument;
use Eclipxe\XmlSchemaValidator\SchemaValidator;
use Flat3\Lodata\Controller\Response;
use Illuminate\Testing\TestResponse;

trait UseSnapshots
{
    protected function assertMetadataSnapshot()
    {
        $response = $this->req(
            (new Request)
                ->path('/$metadata')
                ->xml()
        );

        $xml = $this->getResponseContent($response);
        $this->assertMatchesXmlSnapshot($xml);
        $this->assertResponseHeaderSnapshot($response);

        $document = new DOMDocument();
        $document->loadXML($xml);
        $validator = new SchemaValidator($document);
        $schemas = $validator->buildSchemas();
        $schemas->create('http://docs.oasis-open.org/odata/ns/edm', __DIR__.'/../schemas/edm.xsd');
        $schemas->create('http://docs.oasis-open.org/odata/ns/edmx', __DIR__.'/../schemas/edmx.xsd');
        $validator->validateWithSchemas($schemas);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/$metadata')
        );

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/openapi.json')
        );
    }

    protected function assertResponseHeaderSnapshot(TestResponse $response)
    {
        $this->doSnapshotAssertion([
            'headers' => array_diff_key($response->baseResponse->headers->all(), array_flip(['date'])),
            'status' => $response->baseResponse->getStatusCode(),
        ], new StreamingJsonDriver);
    }

    protected function assertResponseSnapshot(Request $request): TestResponse
    {
        $response = $this->req($request);
        $this->assertResponseHeaderSnapshot($response);
        return $response;
    }

    protected function assertJsonResponseSnapshot(Request $request, int $statusCode = Response::HTTP_OK): TestResponse
    {
        $response = $this->req($request);
        $content = $this->getResponseContent($response);
        $this->assertEquals($statusCode, $response->getStatusCode());
        $this->assertMatchesSnapshot($content, new StreamingJsonDriver);
        return $response;
    }

    protected function assertXmlResponseSnapshot(Request $request): TestResponse
    {
        $response = $this->req($request);
        $this->assertMatchesXmlSnapshot($this->getResponseContent($response));
        $this->assertResponseHeaderSnapshot($response);
        return $response;
    }

    protected function assertHtmlResponseSnapshot(Request $request): TestResponse
    {
        $response = $this->req($request);
        $this->assertMatchesHtmlSnapshot($this->getResponseContent($response));
        return $response;
    }

    protected function assertTextResponseSnapshot(Request $request): TestResponse
    {
        $response = $this->req($request);
        $this->assertMatchesTextSnapshot($this->getResponseContent($response));
        return $response;
    }

    protected function assertJsonMetadataResponse(Request $request, int $statusCode = Response::HTTP_OK): TestResponse
    {
        $response = $this->assertJsonResponseSnapshot($request, $statusCode);
        $this->assertResponseHeaderSnapshot($response);
        return $response;
    }

    protected function assertTextMetadataResponse(Request $request)
    {
        $response = $this->req($request);
        $this->assertMatchesTextSnapshot($this->getResponseContent($response));
        $this->assertResponseHeaderSnapshot($response);
    }
}