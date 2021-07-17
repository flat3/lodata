<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers\SQL;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use PDO;

/**
 * SQL Connection
 * @package Flat3\Lodata\Drivers\SQL
 */
trait SQLConnection
{
    /**
     * Database connection name
     * @var string $connection
     * @internal
     */
    protected $connection = null;

    /**
     * Get the connection name
     * @return string Name
     */
    public function getConnectionName(): string
    {
        return $this->connection ?: DB::getDefaultConnection();
    }

    /**
     * Set the connection name
     * @param  string  $connection  Name
     * @return $this
     */
    public function setConnectionName(string $connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Get the database connection
     * @return ConnectionInterface|Connection Connection
     */
    public function getConnection(): ConnectionInterface
    {
        return DB::connection($this->getConnectionName());
    }

    /**
     * Get a database handle
     * @return PDO Handle
     */
    public function getHandle(): PDO
    {
        $dbh = $this->getConnection()->getPdo();
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $dbh;
    }

    /**
     * Get the PDO driver name
     * @return string Driver
     */
    public function getDriver(): string
    {
        return $this->getHandle()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }
}
