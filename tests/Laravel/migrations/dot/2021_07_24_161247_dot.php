<?php

use Flat3\Lodata\Drivers\SQL\SQLConnection;
use Flat3\Lodata\Drivers\SQLEntitySet;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Dot extends Migration
{
    use SQLConnection;

    public function getDriver(): string
    {
        return DB::connection()->getDriverName();
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (DB::connection()->getDriverName() === SQLEntitySet::SQLite) {
            return;
        }

        DB::statement(
            sprintf(
                "create table dots (%s varchar(255) null, %s varchar(255) null)",
                $this->quoteSingleIdentifier("name.first"),
                $this->quoteSingleIdentifier("name.last")
            )
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (DB::connection()->getDriverName() === SQLEntitySet::SQLite) {
            return;
        }

        Schema::drop('dots');
    }
}
