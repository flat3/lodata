<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Setup;

use Flat3\Lodata\ComplexType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\Discovery;
use Flat3\Lodata\Tests\Laravel\Models\AllAttribute;
use Flat3\Lodata\Tests\Laravel\Models\AllAttributeEnum;
use Flat3\Lodata\Tests\Laravel\Models\Enums\Colour;
use Flat3\Lodata\Tests\Laravel\Models\Enums\MultiColour;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type\Boolean;
use Flat3\Lodata\Type\Byte;
use Flat3\Lodata\Type\Collection;
use Flat3\Lodata\Type\Date;
use Flat3\Lodata\Type\DateTimeOffset;
use Flat3\Lodata\Type\Decimal;
use Flat3\Lodata\Type\Double;
use Flat3\Lodata\Type\Duration;
use Flat3\Lodata\Type\Enum;
use Flat3\Lodata\Type\Guid;
use Flat3\Lodata\Type\Int16;
use Flat3\Lodata\Type\Int32;
use Flat3\Lodata\Type\Int64;
use Flat3\Lodata\Type\SByte;
use Flat3\Lodata\Type\Single;
use Flat3\Lodata\Type\TimeOfDay;
use Flat3\Lodata\Type\UInt16;
use Flat3\Lodata\Type\UInt32;
use Flat3\Lodata\Type\UInt64;

/**
 * @requires PHP >= 8.0
 */
class AttributeDiscoveryTest extends TestCase
{
    public function attributes()
    {
        return [
            'Id' => [
                'Id',
                Guid::class,
                true,
            ],
            'One' => [
                'One',
                Boolean::class,
                null,
                'one',
            ],
            'Two' => [
                'Two',
                Byte::class,
            ],
            'Three' => [
                'Three',
                Collection::class,
            ],
            'ThreeOne' => [
                'ThreeOne',
                Collection::class,
                null,
                null,
                'SByte',
            ],
            'ThreeTwo' => [
                'ThreeTwo',
                Collection::class,
                null,
                null,
                'Recs',
            ],
            'Four' => [
                'Four',
                Date::class,
            ],
            'Five' => [
                'Five',
                DateTimeOffset::class,
            ],
            'Six' => [
                'Six',
                Decimal::class,
            ],
            'Seven' => [
                'Seven',
                Double::class,
            ],
            'Eight' => [
                'Eight',
                Duration::class,
            ],
            'Nine' => [
                'Nine',
                Enum::class,
            ],
            'NineOne' => [
                'NineOne',
                Enum::class,
                null,
                null,
                Colour::class,
            ],
            'NineTwo' => [
                'NineTwo',
                Enum::class,
                null,
                null,
                MultiColour::class,
            ],
            'Ten' => [
                'Ten',
                Int16::class,
            ],
            'Eleven' => [
                'Eleven',
                Int32::class,
            ],
            'Twelve' => [
                'Twelve',
                Int64::class,
            ],
            'Thirteen' => [
                'Thirteen',
                SByte::class,
            ],
            'Fourteen' => [
                'Fourteen',
                Single::class,
            ],
            'Fifteen' => [
                'Fifteen',
                TimeOfDay::class,
            ],
            'Sixteen' => [
                'Sixteen',
                UInt16::class,
            ],
            'Seventeen' => [
                'Seventeen',
                UInt32::class,
            ],
            'Eighteen' => [
                'Eighteen',
                UInt64::class,
            ],
        ];
    }

    public function setUp(): void
    {
        parent::setUp();

        Lodata::add(new ComplexType('Recs'));

        if (Discovery::supportsEnum()) {
            Lodata::discover(AllAttributeEnum::class);
        } else {
            $this->addEnumerationTypes();

            Lodata::discover(AllAttribute::class);
        }
    }

    /**
     * @dataProvider attributes
     */
    public function test_attributes($name, $type, $key = null, $source = null, $underlyingType = null)
    {
        $entitySet = Lodata::getEntitySet('Alternative');
        $entityType = $entitySet->getType();
        $property = $entityType->getDeclaredProperty($name);
        $propertyType = $property->getType();

        $this->assertInstanceOf($type, $propertyType->instance());

        if ($key) {
            $this->assertEquals($name, $entityType->getKey()->getName());
        }

        if ($source) {
            $this->assertEquals($source, $entitySet->getPropertySourceName($property));
        }

        if ($propertyType->instance() instanceof Collection && $underlyingType) {
            $this->assertEquals($underlyingType, $propertyType->getUnderlyingType()->getName());
        }
    }

    public function test_metadata()
    {
        $this->assertMetadataSnapshot();
    }
}