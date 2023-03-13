<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Setup;

use Flat3\Lodata\Annotation\Core\V1\Description;
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
use Flat3\Lodata\Type\String_;
use Flat3\Lodata\Type\TimeOfDay;
use Flat3\Lodata\Type\UInt16;
use Flat3\Lodata\Type\UInt32;
use Flat3\Lodata\Type\UInt64;

/**
 * @requires PHP >= 8.0
 */
class AttributeDiscoveryTest extends TestCase
{
    static public function attributes()
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
                ['type' => 'SByte'],
            ],
            'ThreeTwo' => [
                'ThreeTwo',
                Collection::class,
                null,
                null,
                ['type' => 'Recs'],
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
            'SixOne' => [
                'SixOne',
                Decimal::class,
                null,
                null,
                ['precision' => 5],
            ],
            'SixTwo' => [
                'SixTwo',
                Decimal::class,
                null,
                null,
                ['precision' => 5, 'scale' => 5],
            ],
            'SixThree' => [
                'SixThree',
                Decimal::class,
                null,
                null,
                ['precision' => 5, 'scale' => 'variable'],
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
                ['type' => Colour::class],
            ],
            'NineTwo' => [
                'NineTwo',
                Enum::class,
                null,
                null,
                ['type' => MultiColour::class],
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
                String_::class,
            ],
            'FifteenOne' => [
                'FifteenOne',
                String_::class,
                null,
                null,
                ['maxLength' => 4],
            ],
            'Sixteen' => [
                'Sixteen',
                TimeOfDay::class,
            ],
            'Seventeen' => [
                'Seventeen',
                UInt16::class,
            ],
            'Eighteen' => [
                'Eighteen',
                UInt32::class,
            ],
            'Nineteen' => [
                'Nineteen',
                UInt64::class,
            ],
            'Twenty' => [
                'Twenty',
                String_::class,
                null,
                null,
                ['description' => 'This is the *description*'],
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
    public function test_attributes($name, $type, $key = null, $source = null, $extra = [])
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

        if ($propertyType->instance() instanceof Collection && $extra) {
            $this->assertEquals($extra['type'], $propertyType->getUnderlyingType()->getName());
        }

        if ($extra['precision'] ?? null) {
            $this->assertEquals($extra['precision'], $property->getPrecision());
        }

        if ($extra['scale'] ?? null) {
            $this->assertEquals($extra['scale'], $property->getScale());
        }

        if ($extra['maxLength'] ?? null) {
            $this->assertEquals($extra['maxLength'], $property->getMaxLength());
        }

        if ($extra['description'] ?? null) {
            $this->assertEquals(
                $extra['description'],
                $property->getAnnotations()->firstByClass(Description::class)->toJson()
            );
        }
    }

    public function test_metadata()
    {
        $this->assertMetadataSnapshot();
    }
}
