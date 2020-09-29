<?php

namespace Flat3\OData\Drivers\Database;

use Flat3\OData\Entity;
use Flat3\OData\EntitySet;
use Flat3\OData\Exception\StoreException;
use Flat3\OData\Option\Count;
use Flat3\OData\Option\Filter;
use Flat3\OData\Option\OrderBy;
use Flat3\OData\Option\Search;
use Flat3\OData\Option\Skip;
use Flat3\OData\Option\Top;
use Flat3\OData\Primitive;
use Flat3\OData\Transaction;
use Illuminate\Support\Facades\DB;
use PDO;

class Store extends \Flat3\OData\Store
{
    protected $supportedQueryOptions = [
        Count::class,
        Filter::class,
        OrderBy::class,
        Search::class,
        Skip::class,
        Top::class,
    ];

    /** @var string $table */
    private $table;

    public function getTable(): string
    {
        return $this->table ?: $this->identifier;
    }

    public function setTable(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    public function getDbHandle(): PDO
    {
        return DB::connection()->getPdo();
    }

    public function getDbDriver()
    {
        return $this->getDbHandle()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public function getEntity(Transaction $transaction, Primitive $key): ?Entity
    {
        return $this->getEntitySet($transaction, $key)->getCurrentResultAsEntity();
    }

    public function getEntitySet(Transaction $transaction, ?Primitive $key = null): EntitySet
    {
        $driver = $this->getDbDriver();

        switch ($driver) {
            case 'sqlite':
                return new SQLite\EntitySet($this, $transaction, $key);

            case 'mysql':
                return new MySQL\EntitySet($this, $transaction, $key);

            default:
                return new EntitySet($this, $transaction, $key);
        }
    }

    public function convertResultToEntity($row = null): Entity
    {
        $entity = new Entity($this);

        $key = $this->getTypeKey()->getIdentifier()->get();
        $entity->setEntityIdValue($row[$key]);

        foreach ($row as $id => $value) {
            $property = $this->getTypeProperty($id);

            if (!$property) {
                throw new StoreException(
                    sprintf(
                        'The service attempted to access an undefined property for %s',
                        $id
                    )
                );
            }

            $entity->addPrimitive($value, $property);
        }

        return $entity;
    }
}
