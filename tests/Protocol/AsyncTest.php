<?php

namespace Flat3\Lodata\Tests\Protocol;

use Flat3\Lodata\Controller\Async;
use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Tests\Drivers\WithNumericCollectionDriver;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\Helpers\StreamingJsonDriver;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Transaction\MetadataType\Full;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;

class AsyncTest extends TestCase
{
    use WithNumericCollectionDriver;

    protected function assertStoredResponseMetadata(string $metadata)
    {
        $metadata = json_decode($metadata, true);
        $response = App::make(Response::class);
        $response->headers->replace($metadata['headers']);
        $response->setStatusCode($metadata['status']);
        $this->assertResponseHeaderSnapshot(new TestResponse($response));
    }

    public function async_request(Request $request)
    {
        $queue = Queue::fake();
        $disk = $this->getDisk();

        $request->header('prefer', 'respond-async');

        $acceptedResponse = $this->assertAccepted($request);

        $location = parse_url($acceptedResponse->headers->get('location'), PHP_URL_PATH);

        $acceptedResponse = $this->assertAccepted(
            (new Request)
                ->path($location, false)
        );
        $this->assertResponseHeaderSnapshot($acceptedResponse);

        /** @var Async $job */
        $job = collect($queue->pushedJobs())->flatten(1)->first()['job'];
        $job->handle();

        $this->assertStoredResponseMetadata($disk->get($job->ns('meta')));

        if ($request->headers['accept'] === 'application/json') {
            $this->assertMatchesSnapshot($disk->get($job->ns('data')), new StreamingJsonDriver());

            $this->assertResponseHeaderSnapshot($this->assertJsonResponseSnapshot(
                (new Request)
                    ->path($location, false)
            ));
        } else {
            $this->assertMatchesSnapshot($disk->get($job->ns('data')));

            $this->assertResponseHeaderSnapshot($this->assertTextResponseSnapshot(
                (new Request)
                    ->path($location, false)
            ));
        }

        $this->assertNotFound(
            (new Request)
                ->path($location, false)
        );
    }

    public function test_cancellation()
    {
        $queue = Queue::fake();

        $acceptedResponse = $this->assertAccepted(
            (new Request)
                ->header('prefer', 'respond-async')
        );
        $this->assertResponseHeaderSnapshot($acceptedResponse);

        $location = parse_url($acceptedResponse->headers->get('location'), PHP_URL_PATH);

        $this->assertResponseSnapshot(
            (new Request)
                ->delete()
                ->path($location, false)
        );

        $this->assertNotFound(
            (new Request)
                ->path($location, false)
        );

        /** @var Async $job */
        $job = collect($queue->pushedJobs())->flatten(1)->first()['job'];
        $job->handle();

        $this->assertFalse($job->getFilesystem()->exists($job->getMetaPath()));
        $this->assertFalse($job->getFilesystem()->exists($job->getDataPath()));
    }

    public function test_error()
    {
        $queue = Queue::fake();
        $disk = $this->getDisk();

        $acceptedResponse = $this->assertAccepted(
            (new Request)
                ->path('/nonexistent')
                ->header('prefer', 'respond-async')
        );
        $this->assertResponseHeaderSnapshot($acceptedResponse);

        $location = parse_url($acceptedResponse->headers->get('location'), PHP_URL_PATH);

        $this->assertAccepted(
            (new Request)
                ->path($location, false)
        );

        /** @var Async $job */
        $job = collect($queue->pushedJobs())->flatten(1)->first()['job'];
        $job->handle();

        $this->assertStoredResponseMetadata($disk->get($job->ns('meta')));

        $response = $this->assertJsonMetadataResponse(
            (new Request)
                ->path($location, false)
        );

        $response->streamedContent();

        $this->assertNotFound(
            (new Request)
                ->path($location, false)
        );
    }

    public function test_callback()
    {
        $queue = Queue::fake();
        $disk = $this->getDisk();
        Http::fake();

        $url = 'http://localhost/example';

        $acceptedResponse = $this->assertAccepted(
            (new Request)
                ->header('prefer', 'respond-async,callback;url="'.$url.'"')
        );
        $this->assertResponseHeaderSnapshot($acceptedResponse);

        $location = parse_url($acceptedResponse->headers->get('location'), PHP_URL_PATH);

        $this->assertAccepted(
            (new Request)
                ->path($location, false)
        );

        /** @var Async $job */
        $job = collect($queue->pushedJobs())->flatten(1)->first()['job'];
        $job->handle();

        Http::assertSent(function ($request) use ($url) {
            return $request->url() == $url;
        });

        $this->assertMatchesSnapshot($disk->get($job->ns('data')), new StreamingJsonDriver());
        $this->assertStoredResponseMetadata($disk->get($job->ns('meta')));

        $this->assertResponseHeaderSnapshot($this->assertJsonResponseSnapshot(
            (new Request)
                ->path($location, false)
        ));

        $this->assertNotFound(
            (new Request)
                ->path($location, false)
        );
    }

    public function test_async()
    {
        $this->async_request(
            (new Request)
        );
    }

    public function test_async_metadata()
    {
        $this->async_request(
            (new Request)
                ->xml()
                ->path('/$metadata')
        );
    }

    public function test_async_entityset()
    {
        $this->async_request(
            (new Request)
                ->path('/flights')
        );
    }

    public function test_async_full_metadata()
    {
        $this->async_request(
            (new Request)
                ->path('/flights')
                ->metadata(Full::name)
        );
    }

    public function test_async_batch()
    {
        $this->async_request(
            (new Request)
                ->path('/$batch')
                ->header('content-type', 'multipart/mixed; boundary=batch_36522ad7-fc75-4b56-8c71-56071383e77b')
                ->post()
                ->multipart(<<<MULTIPART
--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: application/http

GET flights(1)
Host: localhost


--batch_36522ad7-fc75-4b56-8c71-56071383e77b
MULTIPART
                )
        );
    }

    public function test_async_batch_json()
    {
        $this->async_request(
            (new Request)
                ->path('/$batch')
                ->post()
                ->body([
                    'requests' => [
                        [
                            'id' => 0,
                            'method' => 'get',
                            'url' => 'flights(1)'
                        ]
                    ]
                ])
        );
    }

    public function test_async_batch_service_metadata()
    {
        $this->async_request(
            (new Request)
                ->path('/$batch')
                ->header('content-type', 'multipart/mixed; boundary=batch_36522ad7-fc75-4b56-8c71-56071383e77b')
                ->post()
                ->multipart(<<<'MULTIPART'
--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: application/http

GET /odata/$metadata
Host: localhost
Accept: application/xml

--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: application/http

GET /odata/
Host: localhost
Content-Type: application/json

--batch_36522ad7-fc75-4b56-8c71-56071383e77b
MULTIPART
                )
        );
    }
}