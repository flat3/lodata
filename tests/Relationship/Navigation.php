<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Relationship;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Transaction\MetadataType;

abstract class Navigation extends TestCase
{
    public function test_update_link_v1()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/'.$this->petEntitySet.'(3)')
                ->body([])
        );

        $this->assertNoContent(
            (new Request)
                ->post()
                ->path('/'.$this->entitySet.'(1)/MyPets(3)/$ref')
                ->body([])
        );

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/'.$this->petEntitySet.'(3)')
                ->body([])
        );
    }

    public function test_update_link_v2()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/'.$this->petEntitySet.'(3)')
                ->body([])
        );

        $this->assertNoContent(
            (new Request)
                ->post()
                ->path('/'.$this->entitySet.'(1)/MyPets/$ref')
                ->query('id', '/odata/'.$this->petEntitySet.'(3)')
                ->body([])
        );

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/'.$this->petEntitySet.'(3)')
                ->body([])
        );
    }

    public function test_update_link_v3()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/'.$this->petEntitySet.'(3)')
                ->body([])
        );

        $this->assertNoContent(
            (new Request)
                ->post()
                ->path('/'.$this->entitySet.'(1)/MyPets/$ref')
                ->body(['@odata.id' => '/odata/'.$this->petEntitySet.'(3)'])
        );

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/'.$this->petEntitySet.'(3)')
                ->body([])
        );
    }

    public function test_link_not_found()
    {
        $this->assertNotFound(
            (new Request)
                ->post()
                ->path('/'.$this->entitySet.'(1)/MyPets(99)/$ref')
                ->body([])
        );
    }
}
