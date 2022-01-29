<?php

namespace Flat3\Lodata\Tests\Laravel\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public function airports()
    {
        return $this->hasMany(Airport::class);
    }
}
