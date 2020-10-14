<?php

namespace Flat3\Lodata\Tests\Unit\Eloquent;

use Flat3\Lodata\Drivers\EloquentEntitySet;
use Flat3\Lodata\Model;
use Flat3\Lodata\Tests\Models\Airport;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class EloquentTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withEloquentModel();
        Model::add(new EloquentEntitySet(Airport::class));
    }

    public function test_read()
    {
        $model = new Airport();
        $model['name'] = 'Eloquent';
        $model['code'] = 'elo';
        $model->save();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports(1)')
        );
    }

    public function test_read_alternative_key()
    {
        $model = new Airport();
        $model['name'] = 'Eloquent';
        $model['code'] = 'elo';
        $model->save();

        $this->assertJsonResponse(
            Request::factory()
                ->path("/airports(code='elo')")
        );
    }

    public function test_update()
    {
        $model = new Airport();
        $model['name'] = 'Eloquent';
        $model['code'] = 'elo';
        $model->save();

        $this->assertJsonResponse(
            Request::factory()
                ->patch()
                ->body([
                    'code' => 'efo',
                ])
                ->path('/airports(1)')
        );

        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports(1)')
        );
    }

    public function test_create()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->post()
                ->body([
                    'code' => 'efo',
                    'name' => 'Eloquent',
                ])
                ->path('/airports')
        );

        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports(1)')
        );
    }

    public function test_delete()
    {
        $model = new Airport();
        $model['name'] = 'Eloquent';
        $model['code'] = 'elo';
        $model->save();

        $this->assertNoContent(
            Request::factory()
                ->delete()
                ->path('/airports(1)')
        );

        $this->assertNotFound(
            Request::factory()
                ->path('/airports(1)')
        );
    }
}