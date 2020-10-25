<?php

namespace Flat3\Lodata\Drivers\SQL;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;

trait SQLConnection
{
    protected $connection = null;

    public function getConnectionName(): string
    {
        return $this->connection ?: DB::getDefaultConnection();
    }

    public function setConnectionName(string $connection)
    {
        $this->connection = $connection;

        return $this;
    }

    public function getConnection(): ConnectionInterface
    {
        return DB::connection($this->getConnectionName());
    }

    public function getHandle(): PDO
    {
        $dbh = $this->getConnection()->getPdo();
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $dbh;
    }

    public function getDriver(): string
    {
        return $this->getHandle()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public function getSchemaBuilder(): Builder
    {
        return Schema::connection($this->getConnectionName());
    }
}
