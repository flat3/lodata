<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\OrderBy;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;

abstract class OrderBy extends TestCase
{
    public function test_orderby()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->orderby('name')
                ->path($this->entitySetPath)
        );
    }

    public function test_orderby_multiple()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->orderby('name asc, age desc')
                ->path($this->entitySetPath)
        );
    }

    public function test_orderby_desc()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->orderby('name desc')
        );
    }

    public function test_orderby_asc()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->orderby('name asc')
        );
    }

    public function test_orderby_invalid()
    {
        $this->assertBadRequest(
            (new Request)
                ->path($this->entitySetPath)
                ->orderby('name wrong')
        );
    }

    public function test_orderby_invalid_property()
    {
        $this->assertBadRequest(
            (new Request)
                ->path($this->entitySetPath)
                ->orderby('invalid asc')
        );
    }

    public function test_orderby_invalid_multiple()
    {
        $this->assertBadRequest(
            (new Request)
                ->path($this->entitySetPath)
                ->orderby('name asc age desc')
        );
    }

    public function test_orderby_navigation_property()
    {
        if (!Lodata::getEntitySet($this->entitySet)?->getType()->getNavigationProperties()->get('flight')) {
            $this->markTestSkipped('Driver does not configure flight navigation property');
        }

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->orderby('flight/origin asc')
        );
    }

    public function test_orderby_navigation_property_desc()
    {
        if (!Lodata::getEntitySet($this->entitySet)?->getType()->getNavigationProperties()->get('flight')) {
            $this->markTestSkipped('Driver does not configure flight navigation property');
        }

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->orderby('flight/origin desc')
        );
    }

    public function test_orderby_navigation_property_invalid()
    {
        $this->assertBadRequest(
            (new Request)
                ->path($this->entitySetPath)
                ->orderby('nonexistent/foo asc')
        );
    }

    public function test_orderby_navigation_property_collection()
    {
        if (!Lodata::getEntitySet($this->flightEntitySet)) {
            $this->markTestSkipped('Driver does not configure flight entity set');
        }

        $this->assertBadRequest(
            (new Request)
                ->path($this->flightEntitySetPath)
                ->orderby('passengers/name asc')
        );
    }
}
