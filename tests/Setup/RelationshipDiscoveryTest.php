<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Setup;

use Flat3\Lodata\Attributes\LodataIdentifier;
use Flat3\Lodata\Attributes\LodataInt32;
use Flat3\Lodata\Attributes\LodataRelationship;
use Flat3\Lodata\Attributes\LodataString;
use Flat3\Lodata\Attributes\LodataTypeIdentifier;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @requires PHP >= 8
 */
class RelationshipDiscoveryTest extends TestCase
{
    protected $migrations = __DIR__.'/Laravel/migrations/variantid';

    public function test_discovery()
    {
        Lodata::discover(Person::class);
        $this->assertMetadataSnapshot();
    }
}

#[LodataIdentifier('People')]
#[LodataTypeIdentifier('Person')]
#[LodataInt32('PersonID', key: true, source: 'person_id')]
#[LodataString('PersonName', source: 'name')]
class Person extends Model
{
    public $primaryKey = 'person_id';

    #[LodataRelationship]
    public function pets(): HasMany
    {
        return $this->hasMany(Pet::class, 'owner_id', 'person_id');
    }
}

#[LodataIdentifier('Pets')]
#[LodataTypeIdentifier('Pet')]
#[LodataInt32('PetID', key: true, source: 'pet_id')]
#[LodataString('PetName', source: 'name')]
#[LodataInt32('OwnerID', source: 'owner_id')]
class Pet extends Model
{
    public $primaryKey = 'pet_id';

    #[LodataRelationship]
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'owner_id', 'person_id');
    }
}