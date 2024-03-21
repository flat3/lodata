<?php

namespace Flat3\Lodata\Drivers\SQL\PDO;

use Doctrine\DBAL\Driver\AbstractSQLiteDriver;
use Flat3\Lodata\Drivers\SQL\PDO\Concerns\ConnectsToDatabase;

class SQLiteDriver extends AbstractSQLiteDriver
{
    use ConnectsToDatabase;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'pdo_sqlite';
    }
}
