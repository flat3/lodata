<?php

namespace Flat3\Lodata\Tests\Laravel\Models;

use Illuminate\Database\Eloquent\Model;

class Cast extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'id' => 'integer',
    ];
}
