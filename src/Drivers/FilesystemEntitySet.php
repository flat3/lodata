<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers;

use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Protocol\ConflictException;
use Flat3\Lodata\Exception\Protocol\NotFoundException;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Helper\PropertyValues;
use Flat3\Lodata\Interfaces\EntitySet\ComputeInterface;
use Flat3\Lodata\Interfaces\EntitySet\CreateInterface;
use Flat3\Lodata\Interfaces\EntitySet\DeleteInterface;
use Flat3\Lodata\Interfaces\EntitySet\QueryInterface;
use Flat3\Lodata\Interfaces\EntitySet\ReadInterface;
use Flat3\Lodata\Interfaces\EntitySet\UpdateInterface;
use Flat3\Lodata\Traits\HasDisk;
use Flat3\Lodata\Transaction\MediaType;
use Generator;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Support\Facades\Storage;

/**
 * Class FilesystemEntitySet
 * @package Flat3\Lodata\Drivers
 */
class FilesystemEntitySet extends EntitySet implements ReadInterface, CreateInterface, UpdateInterface, DeleteInterface, QueryInterface, ComputeInterface
{
    use HasDisk;

    public function __construct(string $identifier, EntityType $entityType)
    {
        parent::__construct($identifier, $entityType);
        $this->setDisk(Storage::disk());
    }

    /**
     * Query
     */
    public function query(): Generator
    {
        $contents = $this->getFilesystem()->listContents('', true);

        foreach ($contents as $content) {
            yield $this->fromMetadata($content);
        }
    }

    /**
     * Read a filesystem entity
     * @param  PropertyValue  $key  Entity ID
     * @return Entity|null
     */
    public function read(PropertyValue $key): Entity
    {
        $metadata = $this->getFilesystem()->getMetadata($this->getEntityIdPath($key));

        if (null === $metadata) {
            throw new NotFoundException('entity_not_found', 'Entity not found');
        }

        return $this->fromMetadata($metadata);
    }

    /**
     * Create a filesystem entity
     * @param  PropertyValues  $propertyValues  Property values
     * @return Entity Entity
     */
    public function create(PropertyValues $propertyValues): Entity
    {
        $entity = $this->newEntity();
        $body = $this->transaction->getBodyAsArray();

        $path = $body['path'];

        if ($this->getFilesystem()->exists($path)) {
            throw new ConflictException('path_exists', 'The requested path already exists');
        }

        $entity->setEntityId($body['path']);
        $this->getFilesystem()->put($path, base64_decode($body['$value'] ?? ''));
        $entity['size'] = $this->getFilesystem()->size($path);

        return $this->read($entity->getEntityId());
    }

    /**
     * Delete a filesystem entity
     * @param  PropertyValue  $key  Entity ID
     */
    public function delete(PropertyValue $key): void
    {
        $this->getFilesystem()->delete($this->getEntityIdPath($key));
    }

    /**
     * Get a disk path from the entity id
     * @param  PropertyValue  $key  Entity ID
     * @return string Path
     */
    public function getEntityIdPath(PropertyValue $key): string
    {
        return $key->getPrimitiveValue();
    }

    /**
     * Update a filesystem entity
     * @param  PropertyValue  $key  Entity ID
     * @param  PropertyValues  $propertyValues  Property values
     * @return Entity Entity
     */
    public function update(PropertyValue $key, PropertyValues $propertyValues): Entity
    {
        $entity = $this->read($key);

        $body = $this->transaction->getBodyAsArray();
        $path = $this->getEntityIdPath($entity->getEntityId());

        if (array_key_exists('$value', $body)) {
            $this->getFilesystem()->put($path, base64_decode($body['$value']));
        }

        return $this->read($key);
    }

    /**
     * Create an entity from filesystem metadata
     * @param  array  $metadata  Metadata
     * @return Entity
     */
    public function fromMetadata(array $metadata): Entity
    {
        $record = [];

        $record['type'] = $this->getFilesystem()->mimeType($metadata['path']);

        foreach (['path', 'timestamp', 'size'] as $meta) {
            $record[$meta] = $metadata[$meta];
        }

        $entity = $this->toEntity($record);

        $disk = $this->getFilesystem();
        $path = $entity->getEntityId()->getPrimitiveValue();

        $contentProperty = $this->getType()->getProperty('content');
        $entity->addPropertyValue(
            $entity->newPropertyValue()->setProperty($contentProperty)->setValue(
                $contentProperty->getType()->instance()
                    ->set($disk->readStream($path))
                    ->setContentType((new MediaType)->parse($disk->mimeType($path)))
                    ->setReadLink(new Uri($disk->url($path)))
            )
        );

        $entity->generateComputedProperties();

        return $entity;
    }
}