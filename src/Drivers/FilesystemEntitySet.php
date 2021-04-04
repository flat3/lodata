<?php

namespace Flat3\Lodata\Drivers;

use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\ConflictException;
use Flat3\Lodata\Exception\Protocol\NotFoundException;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Interfaces\EntitySet\CreateInterface;
use Flat3\Lodata\Interfaces\EntitySet\DeleteInterface;
use Flat3\Lodata\Interfaces\EntitySet\QueryInterface;
use Flat3\Lodata\Interfaces\EntitySet\ReadInterface;
use Flat3\Lodata\Interfaces\EntitySet\UpdateInterface;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FileNotFoundException;

/**
 * Class FilesystemEntitySet
 * @package Flat3\Lodata\Drivers
 */
class FilesystemEntitySet extends EntitySet implements ReadInterface, CreateInterface, UpdateInterface, DeleteInterface, QueryInterface
{
    /** @var FilesystemAdapter $disk */
    protected $disk;

    public function __construct(string $identifier, EntityType $entityType)
    {
        parent::__construct($identifier, $entityType);
        $this->disk = Storage::disk();
    }

    /**
     * Set the disk by name
     * @param  string  $name  Disk name
     * @return $this
     */
    public function setDiskName(string $name): self
    {
        $this->disk = Storage::disk($name);

        return $this;
    }

    /**
     * Set the disk by filesystem adaptor
     * @param  FilesystemAdapter  $disk  Filesystem
     * @return $this
     */
    public function setDisk(FilesystemAdapter $disk): self
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * Get the attached disk
     * @return FilesystemAdapter Disk
     */
    public function getDisk(): FilesystemAdapter
    {
        return $this->disk;
    }

    /**
     * Create a new media entity
     * @return FilesystemEntity Entity
     */
    public function newEntity(): Entity
    {
        $entity = new FilesystemEntity();
        $entity->setEntitySet($this);

        return $entity;
    }

    /**
     * Query
     * @return array
     */
    public function query(): array
    {
        $contents = $this->disk->getDriver()->listContents('', true);
        $results = [];

        foreach ($contents as $content) {
            $results[] = $this->newEntity()->fromMetadata($content);
        }

        return $results;
    }

    /**
     * Read a filesystem entity
     * @param  PropertyValue  $key  Entity ID
     * @return FilesystemEntity|null
     */
    public function read(PropertyValue $key): ?Entity
    {
        try {
            $metadata = $this->disk->getMetadata($this->getEntityIdPath($key));
        } catch (FileNotFoundException $e) {
            throw new NotFoundException();
        }

        return $this->newEntity()->fromMetadata($metadata);
    }

    /**
     * Create a filesystem entity
     * @return FilesystemEntity Entity
     */
    public function create(): Entity
    {
        $entity = $this->newEntity();
        $body = $this->transaction->getBody();

        if (!array_key_exists('path', $body)) {
            throw new BadRequestException('missing_path', 'The path key must be provided');
        }

        $path = $body['path'];

        if ($this->getDisk()->exists($path)) {
            throw new ConflictException('path_exists', 'The requested path already exists');
        }

        $entity->setEntityId($body['path']);
        $this->disk->put($path, base64_decode($body['$value'] ?? ''));
        $entity['size'] = $this->disk->size($path);

        return $this->read($entity->getEntityId());
    }

    /**
     * Delete a filesystem entity
     * @param  PropertyValue  $key  Entity ID
     */
    public function delete(PropertyValue $key): void
    {
        $this->disk->delete($this->getEntityIdPath($key));
    }

    /**
     * Get a disk path from the entity id
     * @param  PropertyValue  $key  Entity ID
     * @return string Path
     */
    public function getEntityIdPath(PropertyValue $key): string
    {
        return $key->getPrimitiveValue()->get();
    }

    /**
     * Update a filesystem entity
     * @param  PropertyValue  $key  Entity ID
     * @return FilesystemEntity Entity
     */
    public function update(PropertyValue $key): Entity
    {
        $entity = $this->read($key);
        $body = $this->transaction->getBody();
        $path = $this->getEntityIdPath($entity->getEntityId());

        if (array_key_exists('$value', $body)) {
            try {
                $this->disk->update($path, base64_decode($body['$value']));
            } catch (FileNotFoundException $e) {
                throw new NotFoundException();
            }
        }

        $entity['size'] = $this->disk->size($path);

        return $entity;
    }
}