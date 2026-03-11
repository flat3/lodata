<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers\SQL;

use Flat3\Lodata\Helper\DBAL;
use PDO;

/**
 * SQL Connection
 * @package Flat3\Lodata\Drivers\SQL
 */
trait SQLConnection
{
    /**
     * Cached DBAL instance to avoid recreating DoctrineConnection per call
     * @var DBAL|null $dbalInstance
     */
    private ?DBAL $dbalInstance = null;

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
        return $this->getDatabase()->quoteSingleIdentifier($identifier);
    }

    /**
     * Get an instance of the SQL Expression container
     *
     * @return SQLExpression
     */
    public function getSQLExpression(): SQLExpression
    {
        return new SQLExpression($this);
    }

    /**
     * Return an instance of the DBAL compatibility layer
     *
     * @return DBAL
     */
    public function getDatabase(): DBAL
    {
        if ($this->dbalInstance === null) {
            $this->dbalInstance = app(DBAL::class, ['connection' => $this->getConnection()]);
        }

        return $this->dbalInstance;
    }
}
