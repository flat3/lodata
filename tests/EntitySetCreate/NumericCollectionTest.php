<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\EntitySetCreate;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Tests\Drivers\WithNumericCollectionDriver;
use Flat3\Lodata\Tests\Helpers\Request;

/**
 * @group numeric-collection
 */
class NumericCollectionTest extends EntitySetCreate
{
    use WithNumericCollectionDriver;

    public function test_create()
    {
        $this->assertJsonMetadataResponse(
            (new Request)
                ->post()
                ->path($this->entitySetPath)
                ->body([
                    'name' => 'Zeta',
                ]),
            Response::HTTP_CREATED
        );

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
        );

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath.'/3')
        );
    }

    public function test_create_positional()
    {
        $this->assertJsonMetadataResponse(
            (new Request)
                ->post()
                ->path($this->entitySetPath)
                ->index('1')
                ->body([
                    'name' => 'Zeta',
                ]),
            Response::HTTP_CREATED
        );

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
        );

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath.'/1')
        );
    }
}
