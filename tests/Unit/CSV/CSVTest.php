<?php

namespace Flat3\Lodata\Tests\Unit\CSV;

use Flat3\Lodata\Drivers\CSVEntitySet;
use Flat3\Lodata\Drivers\CSVEntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Transaction\MetadataType;
use Flat3\Lodata\Type;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;
use SplTempFileObject;

class CSVTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('testing');

        $csv = Writer::createFromFileObject(new SplTempFileObject());
        for ($id = 0; $id < 99; $id++) {
            $csv->insertOne([
                $this->faker->name(),
                $this->faker->dateTime('2014-02-25 08:37:17')->format('Y-m-d\TH:i:sP'),
                $this->faker->randomFloat(),
            ]);
        }
        $disk->write('test.csv', $csv->toString());

        $entityType = new CSVEntityType('entry');
        $entityType->addDeclaredProperty('name', Type::string());
        $entityType->addDeclaredProperty('datetime', Type::datetimeoffset());
        $entityType->addDeclaredProperty('duration', Type::duration());

        $entitySet = new CSVEntitySet('csv', $entityType);
        $entitySet->setDisk($disk);
        $entitySet->setFilePath('test.csv');
        Lodata::add($entitySet);
    }

    public function test_metadata()
    {
        $this->assertMetadataDocuments();
    }

    public function test_set()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/csv')
        );
    }

    public function test_pagination()
    {
        $page = $this->getResponseBody(
            $this->assertJsonResponse(
                (new Request)
                    ->top(4)
                    ->skip(5)
                    ->path('/csv')
            )
        );

        $this->assertJsonResponse(
            $this->urlToReq($page->{'@nextLink'})
        );
    }

    public function test_orderby()
    {
        $this->assertJsonResponse(
            (new Request)
                ->top(4)
                ->metadata(MetadataType\Full::name)
                ->orderby('name')
                ->path('/csv')
        );
    }

    public function test_orderby_direction()
    {
        $this->assertJsonResponse(
            (new Request)
                ->top(4)
                ->metadata(MetadataType\Full::name)
                ->orderby('name desc')
                ->path('/csv')
        );
    }

    public function test_set_with_metadata()
    {
        $this->assertJsonResponse(
            (new Request)
                ->metadata(MetadataType\Full::name)
                ->path('/csv')
        );
    }

    public function test_count()
    {
        $this->assertJsonResponse(
            (new Request)
                ->text()
                ->path('/csv/$count')
        );
    }

    public function test_read()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/csv(2)')
        );
    }

    public function test_missing()
    {
        $this->assertNotFound(
            (new Request)
                ->path('/csv(9999)')
        );
    }

    public function test_read_with_metadata()
    {
        $this->assertJsonResponse(
            (new Request)
                ->metadata(MetadataType\Full::name)
                ->path('/csv(2)')
        );
    }
}