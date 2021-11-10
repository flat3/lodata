<?php

namespace Flat3\Lodata\Tests\Unit\Redis;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Drivers\RedisEntitySet;
use Flat3\Lodata\Drivers\RedisEntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Transaction\MetadataType;
use Flat3\Lodata\Type;
use Illuminate\Support\Facades\Redis;

class RedisTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $entityType = new RedisEntityType('passenger');
        $entityType->addDeclaredProperty('name', Type::string());
        Lodata::add(new RedisEntitySet('passengers', $entityType));

        // @phpstan-ignore-next-line
        Redis::flushdb();

        $faker = $this->faker;

        for ($i = 0; $i < 7; $i++) {
            // @phpstan-ignore-next-line
            Redis::set($faker->lexify('??????'), serialize([
                'name' => $faker->name(),
            ]));
        }
    }

    public function assertRedisRecord($key): void
    {
        // @phpstan-ignore-next-line
        $this->assertMatchesObjectSnapshot(unserialize(Redis::get($key)));
    }

    public function test_metadata()
    {
        $this->assertMetadataDocuments();
    }

    public function test_set()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/passengers')
        );
    }

    public function test_pagination()
    {
        $page = $this->jsonResponse(
            $this->assertJsonResponse(
                (new Request)
                    ->query('$top', 2)
                    ->path('/passengers')
            )
        );

        $page = $this->jsonResponse(
            $this->assertJsonResponse(
                $this->urlToReq($page->{'@nextLink'})
            )
        );

        $page = $this->jsonResponse(
            $this->assertJsonResponse(
                $this->urlToReq($page->{'@nextLink'})
            )
        );

        $this->assertJsonResponse(
            $this->urlToReq($page->{'@nextLink'})
        );
    }

    public function test_count()
    {
        $this->assertJsonResponse(
            (new Request)
                ->text()
                ->path('/passengers/$count')
        );
    }

    public function test_read()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path("/passengers('ilpmuf')")
        );
    }

    public function test_read_select()
    {
        $this->assertJsonResponse(
            (new Request)
                ->select('key')
                ->path("/passengers('ilpmuf')")
        );
    }

    public function test_read_missing()
    {
        $this->assertNotFound(
            (new Request)
                ->path("/passengers('missing')")
        );
    }

    public function test_update()
    {
        $this->assertJsonResponse(
            (new Request)
                ->body([
                    'name' => 'Diamond Jobleck',
                ])
                ->put()
                ->path("/passengers('ilpmuf')")
        );

        $this->assertRedisRecord('ilpmuf');
    }

    public function test_read_with_metadata()
    {
        $this->assertJsonResponse(
            (new Request)
                ->metadata(MetadataType\Full::name)
                ->path("/passengers('ilpmuf')")
        );
    }

    public function test_delete()
    {
        $this->assertNoContent(
            (new Request)
                ->delete()
                ->path("/passengers('ilpmuf')")
        );

        $this->assertRedisRecord('ilpmuf');
    }

    public function test_create()
    {
        $this->assertJsonResponse(
            (new Request)
                ->body([
                    'key' => '734nt4',
                    'name' => 'whammo',
                ])
                ->post()
                ->path('/passengers'),
            Response::HTTP_CREATED
        );

        $this->assertRedisRecord('734nt4');
    }

    public function test_create_without_key()
    {
        $this->assertBadRequest(
            (new Request)
                ->body([
                    'name' => 'hello',
                ])
                ->post()
                ->path('/passengers')
        );
    }
}