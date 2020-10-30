<?php

namespace Flat3\Lodata\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class Passenger extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public function flight()
    {
        return $this->belongsTo(Flight::class);
    }
}

