<?php

namespace Flat3\Lodata\Tests\Laravel\Models;

use Flat3\Lodata\Attributes\LodataRelationship;
use Illuminate\Database\Eloquent\Model;

class Pet extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = [
        'passenger_id' => 'integer',
    ];

    #[LodataRelationship]
    public function passenger()
    {
        return $this->belongsTo(Passenger::class);
    }
}

