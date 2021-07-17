<?php

declare(strict_types=1);

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
use Flat3\Lodata\Traits\HasDisk;
use Flat3\Lodata\Transaction\MediaType;
use Generator;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FileNotFoundException;

/**
 * Class FilesystemEntitySet
 * @package Flat3\Lodata\Drivers
 */
class FilesystemEntitySet extends EntitySet implements ReadInterface, CreateInterface, UpdateInterface, DeleteInterface, QueryInterface
{
    use HasDisk;

    public function __construct(string $identifier, EntityType $entityType)
    {
        parent::__construct($identifier, $entityType);
        $this->disk = Storage::disk();
    }

    /**
     * Query
     */
    public function query(): Generator
    {
        $contents = $this->disk->getDriver()->listContents('', true);

        foreach ($contents as $content) {
            yield $this->fromMetadata($content);
        }
    }

    /**
     * Read a filesystem entity
     * @param  PropertyValue  $key  Entity ID
     * @return Entity|null
     */
    public function read(PropertyValue $key): ?Entity
    {
        try {
            $metadata = $this->disk->getMetadata($this->getEntityIdPath($key));
        } catch (FileNotFoundException $e) {
            throw new NotFoundException();
        }

        return $this->fromMetadata($metadata);
    }

    /**
     * Create a filesystem entity
     * @return Entity Entity
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
     * @return Entity Entity
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

    /**
     * Create an entity from filesystem metadata
     * @param  array  $metadata  Metadata
     * @return Entity
     */
    public function fromMetadata(array $metadata): Entity
    {
        $entity = $this->newEntity();

        foreach (['type', 'path', 'timestamp', 'size'] as $meta) {
            if (array_key_exists($meta, $metadata)) {
                $entity[$meta] = $metadata[$meta];
            }
        }

        /** @var Filesystem $disk */
        $disk = $this->getDisk();
        $path = $entity->getEntityId()->getPrimitiveValue()->get();

        $contentProperty = $this->getType()->getProperty('content');
        $entity->addProperty(
            $entity->newPropertyValue()->setProperty($contentProperty)->setValue(
                $contentProperty->getType()->instance()
                    ->set($disk->readStream($path))
                    ->setContentType(MediaType::factory()->parse($disk->mimeType($path)))
                    ->setReadLink(new Uri($disk->url($path)))
            )
        );

        return $entity;
    }
}