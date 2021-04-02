<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Drivers\FilesystemEntitySet;
use Flat3\Lodata\Interfaces\ContextInterface;
use Illuminate\Filesystem\FilesystemAdapter;

class MediaEntity extends Entity
{
    public function get(Transaction $transaction, ?ContextInterface $context = null): Response
    {
        $response = parent::get($transaction, $context);

        /** @var FilesystemEntitySet $entitySet */
        $entitySet = $this->getEntitySet();

        /** @var FilesystemAdapter $disk */
        $disk = $entitySet->getDisk();

        $path = $this->getEntityId()->getPrimitiveValue()->get();
        $this->metadata['mediaContentType'] = $disk->mimeType($path);
        $this->metadata['mediaReadLink'] = $disk->url($path);

        return $response;
    }

    public function fromMetadata(array $metadata): self
    {
        foreach (['type', 'path', 'timestamp', 'size'] as $meta) {
            if (array_key_exists($meta, $metadata)) {
                $this[$meta] = $metadata[$meta];
            }
        }

        return $this;
    }
}