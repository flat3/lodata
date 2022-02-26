<?php

namespace Flat3\Lodata\Tests\Laravel\Models;

use Flat3\Lodata\Attributes\LodataEnum;
use Flat3\Lodata\Attributes\LodataRelationship;
use Flat3\Lodata\Tests\Laravel\Models\Enums\Colour;
use Flat3\Lodata\Tests\Laravel\Models\Enums\MultiColour;
use Illuminate\Database\Eloquent\Model;

#[LodataEnum(name: 'colour', enum: Colour::class, isFlags: false)]
#[LodataEnum(name: 'sock_colours', enum: MultiColour::class, isFlags: true)]
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

    #[LodataRelationship]
    public function pets()
    {
        return $this->hasMany(Pet::class);
    }
}

