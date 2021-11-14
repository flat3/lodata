<?php

namespace Flat3\Lodata\Tests\Unit\Protocol;

use Flat3\Lodata\Facades\Lodata;
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
            (new Request)
                ->path('/$batch')
                ->post()
                ->body([])
        );
    }

    public function test_bad_request_format()
    {
        $this->assertBadRequest(
            (new Request)
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
            (new Request)
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
            (new Request)
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
            (new Request)
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
            (new Request)
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
            (new Request)
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
        $aa1 = new Operation\Action('aa1');
        $aa1->setCallable(function (Int32 $a, Int32 $b): Int32 {
            return new Int32($a->get() + $b->get());
        });
        Lodata::add($aa1);

        $this->assertJsonMetadataResponse(
            (new Request)
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
        $aa1 = new Operation\Function_('aa1');
        $aa1->setCallable(function (Int32 $a, Int32 $b): Int32 {
            return new Int32($a->get() + $b->get());
        });
        Lodata::add($aa1);

        $this->assertJsonMetadataResponse(
            (new Request)
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
            (new Request)
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
            (new Request)
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
            (new Request)
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
            (new Request)
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
            (new Request)
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
            (new Request)
                ->path('/$batch')
                ->post()
                ->body([
                    'requests' => [
                        [
                            'id' => 0,
                            'method' => 'get',
                            'url' => '/odata/flights(1)',
                        ],
                        [
                            'id' => 1,
                            'method' => 'post',
                            'url' => '/odata/airports',
                            'headers' => ['content-type' => 'application/json'],
                            'body' => [
                                'name' => 'One',
                                'code' => 'one'
                            ]
                        ],
                        [
                            'id' => 2,
                            'method' => 'patch',
                            'headers' => [
                                'content-type' => 'application/json',
                                'if-match' => 'W/"73fa0e567cdc8392d1869d47b3f0886db629d38780a5f2010ce767900cde7266"',
                            ],
                            'url' => '/odata/airports(1)',
                            'body' => [
                                'code' => 'xyz'
                            ]
                        ],
                        [
                            'id' => 3,
                            'method' => 'get',
                            'url' => '/odata/airports',
                        ],
                    ]
                ])
        );

        $this->assertDatabaseSnapshot();
    }

    public function test_bad_document_content_type()
    {
        $this->assertJsonMetadataResponse(
            (new Request)
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
            (new Request)
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
            (new Request)
                ->path('/$batch')
                ->post()
                ->preference('return', 'minimal')
                ->body([
                    'requests' => [
                        [
                            'id' => 0,
                            'method' => 'post',
                            'url' => '/odata/airports',
                            'headers' => [
                                'content-type' => 'application/json',
                                'prefer' => 'return=minimal',
                            ],
                            'body' => [
                                'name' => 'One',
                                'code' => 'one'
                            ]
                        ],
                        [
                            'id' => 1,
                            'method' => 'patch',
                            'headers' => [
                                'content-type' => 'application/json',
                                'prefer' => 'return=minimal',
                            ],
                            'url' => '/odata/airports(1)',
                            'body' => [
                                'code' => 'xyz'
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
            (new Request)
                ->path('/$batch')
                ->post()
                ->body([
                    'requests' => [
                        [
                            'id' => 0,
                            'method' => 'post',
                            'url' => 'airports',
                            'body' => [
                                'name' => 'Test1',
                                'code' => 'xyz',
                            ]
                        ],
                        [
                            'id' => 1,
                            'method' => 'patch',
                            'url' => '$2',
                            'body' => [
                                'name' => 'Test2',
                                'code' => 'abc',
                            ]
                        ],
                    ]
                ])
        );
    }

    public function test_ifmatch_failed()
    {
        $this->assertJsonMetadataResponse(
            (new Request)
                ->path('/$batch')
                ->post()
                ->body([
                    'requests' => [
                        [
                            'id' => 0,
                            'method' => 'post',
                            'url' => 'airports',
                            'body' => [
                                'name' => 'Test1',
                                'code' => 'xyz',
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
                                'name' => 'Test2',
                                'code' => 'abc',
                            ]
                        ],
                    ]
                ])
        );
    }

    public function test_reference_returned_entity()
    {
        $this->assertJsonMetadataResponse(
            (new Request)
                ->path('/$batch')
                ->post()
                ->body([
                    'requests' => [
                        [
                            'id' => 0,
                            'method' => 'post',
                            'url' => 'airports',
                            'body' => [
                                'name' => 'Test1',
                                'code' => 'xyz',
                            ]
                        ],
                        [
                            'id' => 1,
                            'method' => 'patch',
                            'url' => '$0',
                            'body' => [
                                'name' => 'Test2',
                                'code' => 'abc',
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

