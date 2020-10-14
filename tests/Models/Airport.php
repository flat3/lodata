<?php

namespace Flat3\Lodata\Tests\Models;

use Flat3\Lodata\Traits\Lodata;
use Illuminate\Database\Eloquent\Model;

class Airport extends Model
{
    use Lodata;

    public $timestamps = false;
    protected $guarded = [];
    protected $casts = [
        'construction_date' => 'date',
        'sam_datetime' => 'datetime',
        'open_time' => 'timestamp',
        'review_score' => 'float',
        'is_big' => 'boolean',
    ];
}

