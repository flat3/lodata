<?php

namespace Flat3\Lodata\Drivers;

use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Protocol\NotFoundException;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Interfaces\EntitySet\CreateInterface;
use Flat3\Lodata\Interfaces\EntitySet\DeleteInterface;
use Flat3\Lodata\Interfaces\EntitySet\QueryInterface;
use Flat3\Lodata\Interfaces\EntitySet\ReadInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FileNotFoundException;

/**
 * Filesystem Entity Set
 * @package Flat3\Lodata\Drivers
 */
class FilesystemEntitySet extends EntitySet implements ReadInterface, CreateInterface, DeleteInterface, QueryInterface
{
    /** @var FilesystemAdapter $disk */
    protected $disk;

    public function __construct(string $identifier, EntityType $entityType)
    {
        parent::__construct($identifier, $entityType);
        $this->disk = Storage::disk();
    }

    public function setDiskName(string $name): self
    {
        $this->disk = Storage::disk($name);

        return $this;
    }

    public function setDisk(Filesystem $disk): self
    {
        $this->disk = $disk;

        return $this;
    }

    public function getDisk(): Filesystem
    {
        return $this->disk;
    }

    /**
     * @return FilesystemEntity Entity
     */
    public function newEntity(): Entity
    {
        $entity = new FilesystemEntity();
        $entity->setEntitySet($this);

        return $entity;
    }

    public function query(): array
    {
        $contents = $this->disk->getDriver()->listContents('', true);
        $results = [];

        foreach ($contents as $content) {
            $results[] = $this->newEntity()->fromMetadata($content);
        }

        return $results;
    }

    public function read(PropertyValue $key): ?Entity
    {
        try {
            $metadata = $this->disk->getMetadata($key->getPrimitiveValue()->get());
        } catch (FileNotFoundException $e) {
            throw new NotFoundException();
        }

        return $this->newEntity()->fromMetadata($metadata);
    }

    public function create(): Entity
    {
        $entity = $this->newEntity();
        $body = $this->transaction->getBody();
        $entity->fromArray($body);

        $this->disk->put($entity->getEntityId()->getPrimitiveValue()->get(), '');
        $entity['size'] = 0;

        return $this->read($entity->getEntityId());
    }

    public function delete(PropertyValue $key): void
    {
        $this->disk->delete($key->getPrimitiveValue()->get());
    }
}