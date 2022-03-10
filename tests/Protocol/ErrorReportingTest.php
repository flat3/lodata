<?php

namespace Flat3\Lodata\Tests\Protocol;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Protocol\NotImplementedException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Interfaces\EntitySet\QueryInterface;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\Helpers\StreamingJsonDriver;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type;
use Generator;
use Illuminate\Testing\TestResponse;

class ErrorReportingTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        config(['lodata.streaming' => true]);

        Lodata::add(
            new class(
                'texts',
                Lodata::add(new EntityType('text'))
                    ->addProperty(new DeclaredProperty('a', Type::string()))
            ) extends EntitySet implements QueryInterface {
                public function emitJson(Transaction $transaction): void
                {
                    $transaction->outputJsonObjectStart();
                    $transaction->outputJsonKV(['key' => 'value']);
                    throw new NotImplementedException('not_implemented', 'Error during stream');
                }

                public function query(): Generator
                {
                    yield;
                }
            });
    }

    public function test_error_reporting()
    {
        $this->withExceptionHandling();

        $response = $this->req(
            (new Request)
                ->format('xml')
        );

        $this->assertMatchesJsonSnapshot($response->streamedContent());
    }

    public function test_error_response_body()
    {
        try {
            throw (new NotImplementedException)
                ->code('test')
                ->message('test message')
                ->target('test target')
                ->addDetail('testdetail', 'test details')
                ->addInnerError('internal', 'inner error');
        } catch (NotImplementedException $e) {
            $response = $e->toResponse();
            $testResponse = new TestResponse($response);
            $this->assertMatchesSnapshot($testResponse->streamedContent(), new StreamingJsonDriver());
        }
    }

    public function test_stream_error()
    {
        ob_start();

        $this->assertTextMetadataResponse(
            (new Request)
                ->path('/texts'));

        $this->assertMatchesSnapshot(ob_get_clean());
    }

    public function test_disable_streaming_json()
    {
        $this->assertJsonMetadataResponse(
            (new Request)
                ->accept('application/json;odata.streaming=false')
                ->path('/')
        );
    }

    public function test_stream_buffered_error()
    {
        $this->assertNotImplemented(
            (new Request)
                ->accept('application/json;odata.streaming=false')
                ->path('/texts')
        );
    }

    public function test_stream_buffered_default_error()
    {
        config(['lodata.streaming' => false]);

        $this->assertJsonMetadataResponse(
            (new Request)
                ->path('/texts'),
            Response::HTTP_NOT_IMPLEMENTED
        );
    }
}