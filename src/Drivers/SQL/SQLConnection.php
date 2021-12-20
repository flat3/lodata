<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers\SQL;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Flat3\Lodata\Drivers\SQLEntitySet;
use Flat3\Lodata\Exception\Protocol\ConfigurationException;
use PDO;

/**
 * SQL Connection
 * @package Flat3\Lodata\Drivers\SQL
 */
trait SQLConnection
{
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
     * Modify identifier quote types by driver
     * @param  string  $identifier  String to quote
     * @return string Quoted identifier
     */
    public function quoteSingleIdentifier(string $identifier): string
    {
        switch ($this->getDriver()) {
            case SQLEntitySet::PostgreSQL:
                $driver = new PostgreSQLPlatform;
                break;

            case SQLEntitySet::SQLServer:
                $driver = new SQLServerPlatform;
                break;

            case SQLEntitySet::SQLite:
                $driver = new SqlitePlatform;
                break;

            case SQLEntitySet::MySQL:
                $driver = new MySQLPlatform;
                break;

            default:
                throw new ConfigurationException('invalid_driver', 'An invalid driver was used');
        }

        return $driver->quoteSingleIdentifier($identifier);
    }
}
