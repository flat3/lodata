<?php

namespace Flat3\Lodata\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class Pet extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public function passenger()
    {
        return $this->belongsTo(Passenger::class);
    }
}

