<?php

namespace Flat3\Lodata\Tests\Unit\Eloquent;

use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Models\Cast;
use Flat3\Lodata\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InvalidModelTest extends TestCase
{
    public function testMissingPrimaryKey()
    {
        Schema::create('casts', function (Blueprint $table) {
            $table->string('id');
        });

        try {
            Lodata::discoverEloquentModel(Cast::class);
        } catch (InternalServerErrorException $e) {
            $this->assertProtocolExceptionSnapshot($e);
        }
    }
}
