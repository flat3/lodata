<?php

namespace Flat3\Lodata\Tests\Unit\Queries\EntitySet;

use Flat3\Lodata\Drivers\SQLEntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DefaultValueTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Schema::create('test', function (Blueprint $table) {
            $table->string('a')->default('a');
            $table->string('b')->nullable();
        });
    }

    public function test_default()
    {
        $set = (new SQLEntitySet('tests', new EntityType('test')))->setTable('test');
        $set->discoverProperties();
        Lodata::add($set);

        $this->assertMetadataDocuments();
    }
}