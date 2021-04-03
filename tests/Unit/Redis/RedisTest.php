<?php

namespace Flat3\Lodata\Tests\Unit\Redis;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Drivers\RedisEntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Transaction\Metadata;
use Flat3\Lodata\Type;
use Illuminate\Support\Facades\Redis;

class RedisTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $entityType = new EntityType('passenger');
        $entityType->setKey(new DeclaredProperty('key', Type::string()));
        $entityType->addDeclaredProperty('name', Type::string());
        Lodata::add(new RedisEntitySet('passengers', $entityType));

        // @phpstan-ignore-next-line
        Redis::flushdb();

        foreach ([
                     '7a3465' => [
                         'name' => 'Mary Morgan',
                     ],
                     '23452f' => [
                         'name' => 'Mike Blammo',
                     ],
                     '23152f' => [
                         'name' => 'Wardley Woofle',
                     ],
                     '1237h1' => [
                         'name' => 'Sunday Pantone',
                     ],
                     '238g84' => [
                         'name' => 'Sebastian Organa',
                     ],
                 ] as $id => $passenger) {
            // @phpstan-ignore-next-line
            Redis::set($id, serialize($passenger));
        }
    }

    public function test_metadata()
    {
        $this->assertXmlResponse(
            Request::factory()
                ->path('/$metadata')
                ->xml()
        );
    }

    public function test_set()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/passengers')
        );
    }

    public function test_pagination()
    {
        $page = $this->jsonResponse(
            $this->assertJsonResponse(
                Request::factory()
                    ->query('$top', 2)
                    ->path('/passengers')
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
            Request::factory()
                ->text()
                ->path('/passengers/$count')
        );
    }

    public function test_read()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path("/passengers('7a3465')")
        );
    }

    public function test_read_with_metadata()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->metadata(Metadata\Full::name)
                ->path("/passengers('7a3465')")
        );
    }

    public function test_delete()
    {
        $this->assertNoContent(
            Request::factory()
                ->delete()
                ->path("/passengers('7a3465')")
        );

        // @phpstan-ignore-next-line
        $this->assertNull(Redis::get('7a3465'));
    }

    public function test_create()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->body([
                    'key' => '734nt4',
                    'name' => 'whammo',
                ])
                ->post()
                ->path("/passengers"),
            Response::HTTP_CREATED
        );
    }

    public function test_create_missing_key()
    {
        $this->assertBadRequest(
            Request::factory()
                ->body([
                    'name' => 'hello',
                ])
                ->post()
                ->path('/passengers')
        );
    }
}