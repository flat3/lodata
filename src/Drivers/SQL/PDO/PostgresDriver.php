<?php

namespace Flat3\Lodata\Drivers\SQL\PDO;

use Doctrine\DBAL\Driver\AbstractPostgreSQLDriver;
use Flat3\Lodata\Drivers\SQL\PDO\Concerns\ConnectsToDatabase;

class PostgresDriver extends AbstractPostgreSQLDriver
{
    use ConnectsToDatabase;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'pdo_pgsql';
    }
}
