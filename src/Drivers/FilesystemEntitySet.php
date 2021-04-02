<?php

namespace Flat3\Lodata\Drivers;

use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Exception\Protocol\NotFoundException;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Interfaces\EntitySet\CreateInterface;
use Flat3\Lodata\Interfaces\EntitySet\DeleteInterface;
use Flat3\Lodata\Interfaces\EntitySet\QueryInterface;
use Flat3\Lodata\Interfaces\EntitySet\ReadInterface;
use Flat3\Lodata\MediaEntity;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\FileNotFoundException;

/**
 * Filesystem Entity Set
 * @package Flat3\Lodata\Drivers
 */
class FilesystemEntitySet extends EntitySet implements ReadInterface, CreateInterface, DeleteInterface, QueryInterface
{
    /** @var FilesystemAdapter $disk */
    protected $disk;

    public function __construct(string $identifier, Filesystem $filesystem)
    {
        parent::__construct($identifier, new FilesystemEntityType());
        $this->disk = $filesystem;
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
     * @return MediaEntity Entity
     */
    public function newEntity(): Entity
    {
        $entity = new MediaEntity();
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

    public function delete(PropertyValue $key)
    {
        $this->disk->delete($key->getPrimitiveValue()->get());
    }
}