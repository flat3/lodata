<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers\SQL;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Platforms\SQLServer2012Platform;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
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

    /**
     * Modify quote types by driver
     * @param  string  $param  String to quote
     * @return string
     */
    public function quote(string $param): string
    {
        switch ($this->getDriver()) {
            case 'pgsql':
                return (new PostgreSQL94Platform())->quoteSingleIdentifier($param);
            case 'sqlsrv':
                return (new SQLServer2012Platform())->quoteSingleIdentifier($param);
            case 'sqlite':
                return (new SqlitePlatform())->quoteSingleIdentifier($param);
            case 'mysql':
                return (new MySQLPlatform())->quoteSingleIdentifier($param);
        }

        throw new InternalServerErrorException('invalid_driver', 'An invalid driver was used');
    }
}
