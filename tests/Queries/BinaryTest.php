<?php

namespace Flat3\Lodata\Tests\Queries;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Drivers\SQLEntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BinaryTest extends TestCase
{
    protected $migrations = __DIR__.'/../Laravel/migrations/binary';

    public function setUp(): void
    {
        parent::setUp();

        $type = new EntityType('Example');
        $set = new SQLEntitySet('Examples', $type);
        $set->setTable('examples');
        $set->discoverProperties();
        $type->setKey(new DeclaredProperty('id', Type::guid()));
        Lodata::add($set);

        DB::table('examples')->insert([
            'id' => Str::uuid(),
            'photo' => file_get_contents(sprintf('%s/image.png', $this->getFixturesDirectory())),
        ]);
    }

    public function test_binary_metadata()
    {
        $this->assertMetadataSnapshot();
    }

    public function test_binary_retrieve()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/Examples')
        );
    }

    public function test_binary_post()
    {
        $id = Str::uuid();
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/Examples')
                ->body([
                    'id' => $id,
                    'photo' => base64_encode(file_get_contents(sprintf('%s/image.png', $this->getFixturesDirectory())))
                ])
                ->post(),
            Response::HTTP_CREATED
        );

        $this->assertDatabaseHas('examples', [
            'id' => $id,
            'photo' => file_get_contents(sprintf('%s/image.png', $this->getFixturesDirectory())),
        ]);
    }
}