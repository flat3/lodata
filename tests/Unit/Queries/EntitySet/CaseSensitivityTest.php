<?php

namespace Flat3\Lodata\Tests\Unit\Queries\EntitySet;

use Flat3\Lodata\Drivers\SQLEntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CaseSensitivityTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Schema::create('cI_tTEST', function (Blueprint $table) {
            $table->string('FiRst_N1m3')->nullable();
            $table->string('laSt_N1m3')->nullable();
        });

        DB::table('cI_tTEST')->insert([
            'FiRst_N1m3' => 'first',
            'laSt_N1m3' => 'last',
        ]);
    }

    public function test_query()
    {
        $set = (new SQLEntitySet('cI_tTESTs', new EntityType('cI_tTEST')))->setTable('cI_tTEST');
        $set->discoverProperties();
        Lodata::add($set);

        $this->assertJsonResponse(
            (new Request)
                ->path('/cI_tTESTs')
        );
    }

    public function test_name_change()
    {
        $set = (new SQLEntitySet('cI_tTESTs', new EntityType('cI_tTEST')))->setTable('cI_tTEST');
        $set->discoverProperties();
        Lodata::add($set);

        $set->getIdentifier()->setName('Blammo');

        $this->assertJsonResponse(
            (new Request)
                ->path('/Blammo')
        );
    }

    public function test_property()
    {
        $set = (new SQLEntitySet('cI_tTESTs', new EntityType('cI_tTEST')))->setTable('cI_tTEST');
        $set->discoverProperties();
        Lodata::add($set);
        $property = $set->getType()->getProperty('FiRst_N1m3');
        $property->setName('first_n1m3');
        $set->setPropertySourceName($property, 'FiRst_N1m3');

        $this->assertJsonResponse(
            (new Request)
                ->path('/cI_tTESTs')
        );
    }
}