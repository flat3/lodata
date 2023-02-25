<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class VarId extends Migration
{
    public function up()
    {
        Schema::create('people', function (Blueprint $table) {
            $table->bigIncrements('person_id');
            $table->string('name');
        });

        Schema::create('pets', function (Blueprint $table) {
            $table->bigIncrements('pet_id');
            $table->bigInteger('owner_id')->nullable();
            $table->string('name')->nullable();
        });
    }

    public function down()
    {
        foreach (['people', 'pets'] as $table) {
            Schema::drop($table);
        }
    }
}
