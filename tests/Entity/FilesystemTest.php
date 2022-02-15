<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Entity;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Drivers\WithFilesystemDriver;
use Flat3\Lodata\Tests\Helpers\Request;

class FilesystemTest extends EntityTest
{
    use WithFilesystemDriver;

    public function test_read_select()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath)
                ->select('size')
        );
    }

    public function test_read_property()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath.'/size')
        );
    }

    public function test_read_file_in_directory()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath."('d1%2Fa1.txt')")
        );
    }

    public function test_read_directory()
    {
        $this->assertNotFound(
            (new Request)
                ->path($this->entitySetPath."('d1')")
        );
    }

    public function test_read_file_in_directory_key_as_segment()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath.'/d1%2Fa1.txt')
        );
    }

    public function test_update_with_content()
    {
        $this->getDisk()->put('c1.txt', '');
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->body([
                    '$value' => 'dGVzdA==',
                ])
                ->put()
                ->path($this->entitySetPath."('c1.txt')")
        );

        $this->assertMatchesTextSnapshot($this->getDisk()->get('c1.txt'));
    }

    public function test_media_stream()
    {
        $response = $this->assertFound(
            (new Request)
                ->path($this->entitySetPath."('a1.txt')/content/\$value")
        );

        $this->assertEquals('http://odata.files/a1.txt', $response->headers->get('location'));
    }

    public function test_read_with_embedded_stream()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->select('content')
                ->path($this->entitySetPath."('a1.txt')")
        );
    }

    public function test_update_an_entity_set_primitive()
    {
    }

    public function test_null_no_content()
    {
    }

    public function test_read_an_entity_set_primitive_raw()
    {
    }

    public function test_read_an_entity_set_primitive()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath.'/size')
        );
    }

    public function test_null_raw_no_content()
    {
    }

    public function test_raw_custom_accept()
    {
    }

    public function test_raw_custom_format()
    {
    }

    public function test_read_alternative_key()
    {
    }

    public function test_modified_source_name()
    {
        $passengerSet = Lodata::getEntitySet($this->entitySet);
        $ageProperty = $passengerSet->getType()->getProperty('timestamp');
        $ageProperty->setName('ttimestamp');
        $passengerSet->getType()->getProperties()->reKey();
        $passengerSet->setPropertySourceName($ageProperty, 'timestamp');

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath.'('.$this->escapedEntityId.')')
        );
    }
}