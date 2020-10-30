<?php

namespace Flat3\Lodata\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    public $timestamps = false;

    public function airports()
    {
        return $this->hasMany(Airport::class);
    }
}
