<?php

namespace Flat3\Lodata\Tests\Unit\Protocol;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Interfaces\Operation\ActionInterface;
use Flat3\Lodata\Interfaces\Operation\FunctionInterface;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type\Int32;

class BatchJSONTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->withFlightModel();
    }

    public function test_missing_requests()
    {
        $this->assertBadRequest(
            Request::factory()
                ->path('/$batch')
                ->post()
                ->body([])
        );
    }

    public function test_bad_request_format()
    {
        $this->assertBadRequest(
            Request::factory()
                ->path('/$batch')
                ->post()
                ->body([
                    'requests' => [
                        [
                            'a' => 'b',
                        ]
                    ]
                ])
        );
    }

    public function test_full_url()
    {
        $this->assertJsonMetadataResponse(
            Request::factory()
                ->path('/$batch')
                ->post()
                ->body([
                    'requests' => [
                        [
                            'id' => 0,
                            'method' => 'get',
                            'url' => 'http://localhost/odata/flights(1)',
                        ]
                    ]
                ])
        );
    }

    public function test_absolute_path()
    {
        $this->assertJsonMetadataResponse(
            Request::factory()
                ->path('/$batch')
                ->post()
                ->body([
                    'requests' => [
                        [
                            'id' => 0,
                            'method' => 'get',
                            'url' => '/odata/flights(1)',
                        ]
                    ]
                ])
        );
    }

    public function test_partial_failure()
    {
        $this->assertJsonMetadataResponse(
            Request::factory()
                ->path('/$batch')
                ->post()
                ->body([
                    'requests' => [
                        [
                            'id' => 0,
                            'method' => 'post',
                            'url' => '/odata/airports',
                            'body' => [
                                'name' => 'a',
                                'code' => 'xyy',
                            ],
                        ],
                        [
                            'id' => 1,
                            'method' => 'post',
                            'url' => '/odata/airports',
                            'body' => [
                            ],
                        ]
                    ]
                ])
        );

        $this->assertDatabaseSnapshot();
    }

    public function test_service_document()
    {
        $this->assertJsonMetadataResponse(
            Request::factory()
                ->path('/$batch')
                ->post()
                ->body([
                    'requests' => [
                        [
                            'id' => 0,
                            'method' => 'get',
                            'url' => '/odata/',
                        ]
                    ]
                ])
        );
    }

    public function test_metadata_document()
    {
        $this->assertJsonMetadataResponse(
            Request::factory()
                ->path('/$batch')
                ->post()
                ->body([
                    'requests' => [
                        [
                            'id' => 0,
                            'method' => 'get',
                            'url' => '/odata/$metadata',
                        ]
                    ]
                ])
        );
    }

    public function test_action_invocation()
    {
        Lodata::add(new class('aa1') extends Operation implements ActionInterface {
            public function invoke(Int32 $a, Int32 $b): Int32
            {
                return new Int32($a->get() + $b->get());
            }
        });

        $this->assertJsonMetadataResponse(
            Request::factory()
                ->path('/$batch')
                ->post()
                ->body([
                    'requests' => [
                        [
                            'id' => 0,
                            'method' => 'post',
                            'url' => '/odata/aa1',
                            'headers' => ['content-type' => 'application/json'],
                            'body' => [
                                'a' => 3,
                                'b' => 4
                            ]
                        ]
                    ]
                ])
        );
    }

    public function test_function_invocation()
    {
        Lodata::add(new class('aa1') extends Operation implements FunctionInterface {
            public function invoke(Int32 $a, Int32 $b): Int32
            {
                return new Int32($a->get() + $b->get());
            }
        });

        $this->assertJsonMetadataResponse(
            Request::factory()
                ->path('/$batch')
                ->post()
                ->body([
                    'requests' => [
                        [
                            'id' => 0,
                            'method' => 'get',
                            'url' => '/odata/aa1(a=3,b=4)',
                        ]
                    ]
                ])
        );
    }

    public function test_relative_path()
    {
        $this->assertJsonMetadataResponse(
            Request::factory()
                ->path('/$batch')
                ->post()
                ->body([
                    'requests' => [
                        [
                            'id' => 0,
                            'method' => 'get',
                            'url' => 'flights(1)',
                        ]
                    ]
                ])
        );
    }

    public function test_prefer_metadata()
    {
        $this->assertJsonMetadataResponse(
            Request::factory()
                ->path('/$batch')
                ->post()
                ->body([
                    'requests' => [
                        [
                            'id' => 0,
                            'method' => 'get',
                            'headers' => ['accept' => 'application/json;odata.metadata=full'],
                            'url' => '/odata/flights(1)',
                        ]
                    ]
                ])
        );
    }

    public function test_no_accept_header()
    {
        $this->assertJsonMetadataResponse(
            Request::factory()
                ->path('/$batch')
                ->post()
                ->body([
                    'requests' => [
                        [
                            'id' => 0,
                            'method' => 'get',
                            'url' => '/odata/flights(1)',
                        ]
                    ]
                ])
        );
    }

    public function test_not_found()
    {
        $this->assertJsonMetadataResponse(
            Request::factory()
                ->path('/$batch')
                ->post()
                ->body([
                    'requests' => [
                        [
                            'id' => 0,
                            'method' => 'get',
                            'url' => 'notfound',
                        ]
                    ]
                ])
        );
    }

    public function test_bad_request()
    {
        $this->assertJsonMetadataResponse(
            Request::factory()
                ->path('/$batch')
                ->post()
                ->body([
                    'requests' => [
                        [
                            'id' => 0,
                            'method' => 'get',
                            'url' => "flights('a')",
                        ]
                    ]
                ])
        );
    }

    public function test_batch()
    {
        $this->assertJsonMetadataResponse(
            Request::factory()
                ->path('/$batch')
                ->post()
                ->body([
                    'requests' => [
                        [
                            'id' => 0,
                            'method' => 'get',
                            'url' => "/odata/flights(1)",
                        ],
                        [
                            'id' => 1,
                            'method' => 'post',
                            'url' => "/odata/airports",
                            'headers' => ['content-type' => 'application/json'],
                            'body' => [
                                "name" => "One",
                                "code" => "one"
                            ]
                        ],
                        [
                            'id' => 2,
                            'method' => 'patch',
                            'headers' => [
                                'content-type' => 'application/json',
                                'if-match' => 'W/"192974269b83f1c24b65713ffc921a2e414f1174ee4c1ad2342ce4f9b014f9de"',
                            ],
                            'url' => "/odata/airports(1)",
                            'body' => [
                                "code" => "xyz"
                            ]
                        ],
                        [
                            'id' => 3,
                            'method' => 'get',
                            'url' => "/odata/airports",
                        ],
                    ]
                ])
        );

        $this->assertDatabaseSnapshot();
    }

    public function test_bad_document_content_type()
    {
        $this->assertJsonMetadataResponse(
            Request::factory()
                ->path('/$batch')
                ->post()
                ->body([
                    'requests' => [
                        [
                            'id' => 0,
                            'method' => 'post',
                            'url' => '/odata/airports',
                            'body' => [
                                'name' => 'One',
                                'code' => 'one',
                            ]
                        ]
                    ]
                ])
        );
    }

    public function test_atomicity_group()
    {
        $this->assertNotImplemented(
            Request::factory()
                ->path('/$batch')
                ->post()
                ->body([
                    'requests' => [
                        [
                            'id' => 0,
                            'method' => 'post',
                            'url' => '/odata/airports',
                            'atomicityGroup' => 'a',
                            'body' => [
                                'name' => 'One',
                                'code' => 'one',
                            ]
                        ]
                    ]
                ])
        );
    }

    public function test_prefer_minimal()
    {
        $this->assertJsonMetadataResponse(
            Request::factory()
                ->path('/$batch')
                ->post()
                ->preference('return', 'minimal')
                ->body([
                    'requests' => [
                        [
                            'id' => 0,
                            'method' => 'post',
                            'url' => "/odata/airports",
                            'headers' => [
                                'content-type' => 'application/json',
                                'prefer' => 'return=minimal',
                            ],
                            'body' => [
                                "name" => "One",
                                "code" => "one"
                            ]
                        ],
                        [
                            'id' => 1,
                            'method' => 'patch',
                            'headers' => [
                                'content-type' => 'application/json',
                                'prefer' => 'return=minimal',
                            ],
                            'url' => "/odata/airports(1)",
                            'body' => [
                                "code" => "xyz"
                            ]
                        ]
                    ]
                ])
        );

        $this->assertDatabaseSnapshot();
    }

    public function test_missing_reference()
    {
        $this->assertJsonMetadataResponse(
            Request::factory()
                ->path('/$batch')
                ->post()
                ->body([
                    'requests' => [
                        [
                            'id' => 0,
                            'method' => 'post',
                            'url' => "airports",
                            'body' => [
                                "name" => "Test1",
                                "code" => "xyz",
                            ]
                        ],
                        [
                            'id' => 1,
                            'method' => 'patch',
                            'url' => '$2',
                            'body' => [
                                "name" => "Test2",
                                "code" => "abc",
                            ]
                        ],
                    ]
                ])
        );
    }

    public function test_ifmatch_failed()
    {
        $this->assertJsonMetadataResponse(
            Request::factory()
                ->path('/$batch')
                ->post()
                ->body([
                    'requests' => [
                        [
                            'id' => 0,
                            'method' => 'post',
                            'url' => "airports",
                            'body' => [
                                "name" => "Test1",
                                "code" => "xyz",
                            ]
                        ],
                        [
                            'id' => 1,
                            'method' => 'patch',
                            'url' => '$0',
                            'headers' => [
                                'if-match' => 'xxx',
                            ],
                            'body' => [
                                "name" => "Test2",
                                "code" => "abc",
                            ]
                        ],
                    ]
                ])
        );
    }

    public function test_reference_returned_entity()
    {
        $this->assertJsonMetadataResponse(
            Request::factory()
                ->path('/$batch')
                ->post()
                ->body([
                    'requests' => [
                        [
                            'id' => 0,
                            'method' => 'post',
                            'url' => "airports",
                            'body' => [
                                "name" => "Test1",
                                "code" => "xyz",
                            ]
                        ],
                        [
                            'id' => 1,
                            'method' => 'patch',
                            'url' => '$0',
                            'body' => [
                                "name" => "Test2",
                                "code" => "abc",
                            ]
                        ],
                        [
                            'id' => 2,
                            'method' => 'get',
                            'url' => '$0',
                        ],
                    ]
                ])
        );
    }
}

