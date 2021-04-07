<?php

namespace Flat3\Lodata\Drivers;

use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Interfaces\EntitySet\CountInterface;
use Flat3\Lodata\Interfaces\EntitySet\PaginationInterface;
use Flat3\Lodata\Interfaces\EntitySet\QueryInterface;
use Flat3\Lodata\Interfaces\EntitySet\ReadInterface;
use Flat3\Lodata\Traits\HasDisk;
use Generator;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\TabularDataReader;

class CSVEntitySet extends EntitySet implements ReadInterface, QueryInterface, PaginationInterface, CountInterface
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

    public function count(): int
    {
        return $this->getCsvStatement()->count();
    }

    public function query(): Generator
    {
        $statement = Statement::create();

        if ($this->getSkip()->hasValue()) {
            $statement = $statement->offset($this->getSkip()->getValue());
        }

        if ($this->getTop()->hasValue()) {
            $statement = $statement->limit($this->getTop()->getValue());
        }

        $reader = $statement->process($this->getCsvReader(), $this->getCsvHeader());

        foreach ($reader->getIterator() as $offset => $record) {
            yield $this->newEntity()->setEntityId($offset)->fromArray($record);
        }
    }

    public function read(PropertyValue $key): ?Entity
    {
        $csv = $this->getCsvStatement();
        $row = $csv->fetchOne($key->getPrimitiveValue()->get());
        return $this->newEntity()->setEntityId($key)->fromArray($row);
    }
}