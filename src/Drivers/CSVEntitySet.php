<?php

namespace Flat3\Lodata\Drivers;

use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Interfaces\EntitySet\DeleteInterface;
use Flat3\Lodata\Interfaces\EntitySet\PaginationInterface;
use Flat3\Lodata\Interfaces\EntitySet\QueryInterface;
use Flat3\Lodata\Interfaces\EntitySet\ReadInterface;
use Flat3\Lodata\Traits\HasDisk;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\TabularDataReader;

class CSVEntitySet extends EntitySet implements ReadInterface, DeleteInterface, QueryInterface, PaginationInterface
{
    use HasDisk;

    protected $path = null;

    public function __construct(string $identifier, EntityType $entityType)
    {
        parent::__construct($identifier, $entityType);
        $this->disk = Storage::disk();
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getCsvHeader(): array
    {
        $header = [];

        foreach ($this->getType()->getDeclaredProperties() as $property) {
            if ($property === $this->getType()->getKey()) {
                continue;
            }

            $header[] = $property->getName();
        }

        return $header;
    }

    public function getCsvReader()
    {
        return Reader::createFromStream($this->disk->readStream($this->path));
    }

    public function getCsvStatement(): TabularDataReader
    {
        return Statement::create()->process($this->getCsvReader(), $this->getCsvHeader());
    }

    public function count()
    {
        return $this->getCsvStatement()->count();
    }

    public function query(): array
    {
        $csv = Statement::create()
            ->offset($this->skip)
            ->limit($this->top)
            ->process($this->getCsvReader(), $this->getCsvHeader());

        $results = [];

        foreach ($csv->getRecords() as $offset => $record) {
            $results[] = $this->newEntity()->setEntityId($offset)->fromArray($record);
        }

        return $results;
    }

    public function read(PropertyValue $key): ?Entity
    {
        $csv = $this->getCsvStatement();
        $row = $csv->fetchOne($key->getPrimitiveValue()->get());
        return $this->newEntity()->setEntityId($key)->fromArray($row);
    }

    public function delete(PropertyValue $key): void
    {
        $csv = $this->getCsvStatement();
    }
}