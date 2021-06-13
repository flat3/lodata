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

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function flights()
    {
        return $this->hasMany(Flight::class, 'origin', 'code');
    }

    public function scopeModern($query)
    {
        return $query->where('construction_date', '>', '1940-01-01');
    }
}
