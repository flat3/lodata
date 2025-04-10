<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Navigation;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Transaction\MetadataType;

abstract class Navigation extends TestCase
{
    public function test_expand()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->flightEntitySetPath)
                ->expand('passengers')
        );
    }

    public function test_expand_full_metadata()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->flightEntitySetPath)
                ->metadata(MetadataType\Full::name)
                ->expand('passengers')
        );
    }

    public function test_apply_query_parameters_to_last_segment()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->flightEntitySetPath.'/1/passengers')
                ->select('flight_id,name')
                ->top('1')
        );
    }

    public function test_expand_full_metadata_count()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->flightEntitySetPath)
                ->metadata(MetadataType\Full::name)
                ->expand('passengers($count=true)')
        );
    }

    public function test_expand_full_metadata_top()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->flightEntitySetPath)
                ->metadata(MetadataType\Full::name)
                ->expand('passengers($count=true;$top=1)')
        );
    }

    public function test_expand_property()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->flightEntitySetPath.'/1/passengers')
        );
    }

    public function test_expand_and_select()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->flightEntitySetPath.'/1')
                ->expand('passengers')
                ->select('origin')
        );
    }

    public function test_select_with_expand_select()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->flightEntitySetPath.'/1')
                ->select('origin,destination')
                ->expand('passengers($select=name)')
        );
    }

    public function test_select_with_expand_select_multiple()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->flightEntitySetPath.'/1')
                ->select('origin')
                ->expand('airports($select=name,review_score,construction_date)')
        );
    }

    public function test_select_with_expand_select_multiple_and_top()
    {
        $this->assertPaginationSequence(
            (new Request)
                ->path($this->flightEntitySetPath.'/1')
                ->select('origin')
                ->expand('passengers($select=flight_id,name;$top=2)'),
            PHP_INT_MAX,
            'passengers@nextLink'
        );
    }

    public function test_expand_multiple_with_select()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->airportEntitySetPath.'/1')
                ->expand('flights($select=duration),country($select=name)')
                ->select('code')
        );
    }

    public function test_expand_expand()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->flightEntitySetPath.'/1')
                ->select('id,duration')
                ->expand('passengers($select=dob,age&$expand=MyPets($select=name))')
        );
    }

    public function test_expand_expand_filter_filter()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->flightEntitySetPath)
                ->filter('duration eq PT11H25M0S')
                ->select('id,duration')
                ->expand("passengers(\$filter=age gt 3&\$expand=MyPets(\$filter=name eq 'Banana'))")
        );
    }

    public function test_expand_containing_filter()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->flightEntitySetPath.'/1')
                ->expand("passengers(\$filter=startswith(name, 'Al'))")
        );
    }

    public function test_expand_containing_select()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->flightEntitySetPath.'/1')
                ->expand('passengers($select=name)')
        );
    }

    public function test_expand_containing_orderby()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->flightEntitySetPath.'/1')
                ->expand('passengers($orderby=name desc)')
        );
    }

    public function test_expand_containing_skip()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->flightEntitySetPath.'/1')
                ->expand('passengers($skip=1)')
        );
    }

    public function test_expand_containing_top()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->flightEntitySetPath.'/1')
                ->expand('passengers($top=2)')
        );
    }

    public function test_expand_containing_search()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->flightEntitySetPath.'/1')
                ->expand('passengers($search=pha)')
        );
    }

    public function test_expand_containing_orderby_select()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->flightEntitySetPath.'/1')
                ->expand('passengers($orderby=name desc;$select=name)')
        );
    }

    public function test_expand_containing_function_filter_select()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->flightEntitySetPath.'/1')
                ->expand('passengers($filter=startswith(name, \'Alp\');$select=name)')
        );
    }

    public function test_create_deep()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->flightEntitySetPath)
                ->post()
                ->body([
                    'origin' => 'lhr',
                    'destination' => 'sfo',
                    'duration' => 'PT8H',
                    'passengers' => [
                        [
                            'name' => 'Alice',
                        ],
                        [
                            'name' => 'Bob',
                        ],
                    ],
                ]),
            Response::HTTP_CREATED
        );
    }

    public function test_create_navigation_property()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->post()
                ->body([
                    'name' => 'Harry Horse',
                ])
                ->path($this->flightEntitySetPath.'/1/passengers'),
            Response::HTTP_CREATED
        );
    }

    public function test_expand_entity()
    {
        $this
            ->assertJsonResponseSnapshot(
                (new Request)
                    ->path($this->airportEntitySetPath.'/1')
                    ->expand('flights'))
            ->assertHeaderMissing('etag');
    }

    public function test_expand_hasone_entity()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->airportEntitySetPath.'/1')
                ->expand('country')
        );
    }

    public function test_expand_hasone_entity_property()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->airportEntitySetPath.'/1/country')
        );
    }

    public function test_expand_hasmany_entity()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->flightEntitySetPath.'/1')
                ->expand('passengers')
        );
    }

    public function test_expand_hasmany_entity_expand()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->flightEntitySetPath.'/1')
                ->expand('passengers($expand=flight)')
        );
    }

    public function test_expand_hasmany_entity_expand_full_metadata()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->flightEntitySetPath)
                ->metadata(MetadataType\Full::name)
                ->expand('passengers($expand=flight)')
        );
    }

    public function test_expand_belongsto_entity()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath)
                ->expand('flight')
        );
    }

    public function test_expand_belongsto_property()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath.'/flight')
        );
    }

    public function test_expand_belongsto_property_select_property()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->flightEntitySetPath.'/1/passengers')
                ->select('name')
        );
    }

    public function test_expand_belongsto_property_full_metadata()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->metadata(MetadataType\Full::name)
                ->path($this->entityPath.'/flight')
        );
    }

    public function test_expand_property_invalid_key_segment()
    {
        $this->assertNotFound(
            (new Request)
                ->path($this->countryEntitySet.'/airports')
        );
    }

    public function test_expand_property_entity()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->countryEntitySetPath)
                ->expand('airports')
        );
    }

    public function test_expand_hasonethrough()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->expand('originAirport,destinationAirport')
        );
    }

    public function test_expand_hasone()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->flightEntitySetPath.'/1')
                ->expand('originAirport,destinationAirport')
        );
    }

    public function test_expand_hasone_property()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->flightEntitySetPath.'/1/originAirport')
        );

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->flightEntitySetPath.'/1/destinationAirport')
        );

        $this->assertNoContent(
            (new Request)
                ->path($this->flightEntitySetPath.'/2/destinationAirport')
        );

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->flightEntitySetPath.'/1/destinationAirport/code')
        );

        $this->assertTextResponseSnapshot(
            (new Request)
                ->text()
                ->path($this->flightEntitySetPath.'/1/destinationAirport/code/$value')
        );
    }

    public function test_expand_property_count()
    {
        $this->assertTextResponseSnapshot(
            (new Request)
                ->text()
                ->path($this->airportEntitySetPath.'/1/flights/$count')
        );
    }

    public function test_deep_transaction_failed()
    {
        $this->keepDriverState();

        $this->assertBadRequest(
            (new Request)
                ->path($this->flightEntitySetPath)
                ->post()
                ->body([
                    'origin' => 'lhr',
                    'destination' => 'sfo',
                    'passengers' => [
                        [
                            'name' => 'Alice',
                        ],
                        [
                        ],
                    ],
                ])
        );

        $this->assertDriverStateUnchanged();
    }

    public function test_count_navigation_property()
    {
        $this->assertTextResponseSnapshot(
            (new Request)
                ->path($this->flightEntitySetPath.'/1/passengers/$count')
                ->text()
        );
    }

    public function test_resolve_entity_id_with_expand()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/$entity')
                ->id(sprintf("%s(1)", $this->flightEntitySet))
                ->expand('passengers')
        );
    }

    public function test_update_related()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath)
                ->patch()
                ->body([
                    'name' => 'Zooey Zamblo',
                    'MyPets' => [
                        [
                            '@id' => $this->petEntitySet.'(1)',
                        ],
                        [
                            '@id' => $this->petEntitySet.'(2)',
                            'name' => 'Charlie',
                        ],
                        [
                            'name' => 'Delta',
                        ],
                        [
                            '@id' => $this->petEntitySet.'(2)',
                            '@removed' => [
                                'reason' => 'deleted',
                            ],
                        ]
                    ]
                ])
        );
    }

    public function test_update_related_missing()
    {
        $this->assertBadRequest(
            (new Request)
                ->path($this->entityPath)
                ->patch()
                ->body([
                    'name' => 'Zooey Zamblo',
                    'MyPets' => [
                        [
                            '@id' => 'pets(99)',
                        ],
                    ]
                ])
        );
    }

    public function test_update_removed_changed()
    {
        $this->assertNotImplemented(
            (new Request)
                ->path($this->entityPath)
                ->patch()
                ->body([
                    'MyPets' => [
                        [
                            '@removed' => ['reason' => 'changed'],
                            '@id' => $this->petEntitySet.'(1)',
                        ],
                    ]
                ])
        );
    }

    public function test_rejects_null_properties()
    {
        $this->assertBadRequest(
            (new Request)
                ->path($this->entityPath)
                ->patch()
                ->body([
                    'name' => null,
                ])
        );
    }

    public function test_create_related_entity()
    {
        $this->assertJsonMetadataResponse(
            (new Request)
                ->path($this->flightEntitySet.'(1)/passengers')
                ->post()
                ->body([
                    'name' => 'Henry Horse',
                ]),
            Response::HTTP_CREATED
        );

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->flightEntitySet.'(1)/passengers')
        );
    }

    public function test_create_entity_with_existing_related_entities()
    {
        $this->assertJsonMetadataResponse(
            (new Request)
                ->path($this->flightEntitySetPath)
                ->post()
                ->body([
                    'origin' => 'sfo',
                    'destination' => 'lhr',
                    'passengers' => [
                        [
                            '@id' => $this->entitySet.'(1)',
                        ],
                        [
                            '@id' => $this->entitySet.'(2)',
                        ],
                    ]
                ]),
            Response::HTTP_CREATED
        );
    }

    public function test_create_entity_cannot_modify_existing_related_entities()
    {
        $this->assertBadRequest(
            (new Request)
                ->path($this->flightEntitySetPath)
                ->post()
                ->body([
                    'origin' => 'sfo',
                    'destination' => 'lhr',
                    'passengers' => [
                        [
                            '@id' => 'passengers(1)',
                            'name' => 'Not allowed',
                        ],
                    ]
                ])
        );
    }

    public function test_create_deep_metadata()
    {
        $response = $this->getResponseBody($this->assertJsonMetadataResponse(
            (new Request)
                ->path($this->flightEntitySetPath)
                ->metadata(MetadataType\Full::name)
                ->post()
                ->body([
                    'origin' => 'lhr',
                    'destination' => 'sfo',
                    'passengers' => [
                        [
                            'name' => 'Alice',
                            'MyPets' => [
                                [
                                    'name' => 'Sparkles',
                                    'type' => 'dog',
                                ]
                            ],
                        ],
                        [
                            'name' => 'Bob',
                        ],
                    ],
                ]),
            Response::HTTP_CREATED
        ));

        $this->assertJsonResponseSnapshot(
            $this->urlToReq($response->{'passengers@navigationLink'})
        );

        $this->assertJsonResponseSnapshot(
            $this->urlToReq($response->passengers[0]->{'@readLink'})
        );

        $this->assertJsonResponseSnapshot(
            $this->urlToReq($response->passengers[0]->{'MyPets@navigationLink'})
        );
    }
}
