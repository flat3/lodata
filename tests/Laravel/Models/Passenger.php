<?php

namespace Flat3\Lodata\Tests\Laravel\Models;

use Flat3\Lodata\Attributes\LodataRelationship;
use Illuminate\Database\Eloquent\Model;

class Passenger extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = [
        'flight_id' => 'integer',
        'in_role' => 'float',
        'age' => 'float',
        'chips' => 'boolean',
        'open_time' => 'datetime:H:i:s',
        'dob' => 'datetime:Y-m-d H:i:s',
        'colour' => 'integer',
        'sock_colours' => 'integer',
        'emails' => 'array',
    ];

    public function flight()
    {
        return $this->belongsTo(Flight::class);
    }

    public function originAirport()
    {
        return $this->hasOneThrough(Airport::class, Flight::class, 'id', 'code', 'flight_id', 'origin');
    }

    public function destinationAirport()
    {
        return $this->hasOneThrough(Airport::class, Flight::class, 'id', 'code', 'flight_id', 'destination');
    }

    #[LodataRelationship(name: "MyPets", description: "All my pets")]
    public function pets()
    {
        return $this->hasMany(Pet::class);
    }
}

