<?php

namespace Flat3\Lodata\Drivers;

use Flat3\Lodata\Entity;
use Flat3\Lodata\Transaction\MediaType;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Contracts\Filesystem\Filesystem;

class FilesystemEntity extends Entity
{
    public function fromMetadata(array $metadata): self
    {
        foreach (['type', 'path', 'timestamp', 'size'] as $meta) {
            if (array_key_exists($meta, $metadata)) {
                $this[$meta] = $metadata[$meta];
            }
        }

        /** @var Filesystem $disk */
        $disk = $this->getEntitySet()->getDisk();
        $path = $this->getEntityId()->getPrimitiveValue()->get();

        $contentProperty = $this->getType()->getProperty('content');
        $this->addProperty(
            $this->newPropertyValue()->setProperty($contentProperty)->setValue(
                $contentProperty->getType()->instance()
                    ->set($disk->readStream($path))
                    ->setContentType(MediaType::factory()->parse($disk->mimeType($path)))
                    ->setReadLink(new Uri($disk->url($path)))
            )
        );

        return $this;
    }
}