<?php

namespace Flat3\Lodata\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class Airport extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = [
        'construction_date' => 'date',
        'sam_datetime' => 'datetime',
        'open_time' => 'timestamp',
        'review_score' => 'float',
        'is_big' => 'boolean',
    ];

    public function flights()
    {
        return $this->hasMany(Flight::class, 'origin', 'code');
    }
}
