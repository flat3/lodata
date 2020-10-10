<?php

namespace Flat3\OData\Tests\Unit\Protocol;

use Flat3\OData\Controller\Async;
use Flat3\OData\Tests\JsonDriver;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AsyncTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Str::createUuidsUsing(function () {
            return '00000000-0000-0000-0000-000000000000';
        });
    }

    public function test_async()
    {
        $queue = Queue::fake();
        $disk = Storage::fake('odata');

        $acceptedException = $this->assertAccepted(
            Request::factory()
                ->header('prefer', 'respond-async')
        );

        $location = parse_url($acceptedException->toResponse()->headers->get('location'), PHP_URL_PATH);

        $this->assertAccepted(
            Request::factory()
                ->path($location, false)
        );

        /** @var Async $job */
        $job = collect($queue->pushedJobs())->flatten(1)->first()['job'];
        $job->handle();

        $this->assertMatchesSnapshot($disk->get($job->ns('data')), new JsonDriver());
        $this->assertStoredResponseMetadata($disk->get($job->ns('meta')));

        $this->assertResponseMetadata($this->assertJsonResponse(
            Request::factory()
                ->path($location, false)
        ));

        $this->assertNotFound(
            Request::factory()
                ->path($location, false)
        );
    }

    public function test_cancellation()
    {
        $queue = Queue::fake();
        $disk = Storage::fake('odata');

        $acceptedException = $this->assertAccepted(
            Request::factory()
                ->header('prefer', 'respond-async')
        );

        $location = parse_url($acceptedException->toResponse()->headers->get('location'), PHP_URL_PATH);

        $this->assertMetadataResponse(
            Request::factory()
                ->delete()
                ->path($location, false)
        );

        $this->assertNotFound(
            Request::factory()
                ->path($location, false)
        );

        /** @var Async $job */
        $job = collect($queue->pushedJobs())->flatten(1)->first()['job'];
        $this->assertTrue($job->handle());

        $this->assertFileDoesNotExist($job->getMetaPath());
        $this->assertFileDoesNotExist($job->getDataPath());
    }
}