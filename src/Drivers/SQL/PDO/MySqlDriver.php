<?php

namespace Flat3\Lodata\Drivers\SQL\PDO;

use Doctrine\DBAL\Driver\AbstractMySQLDriver;
use Flat3\Lodata\Drivers\SQL\PDO\Concerns\ConnectsToDatabase;

class MySqlDriver extends AbstractMySQLDriver
{
    use ConnectsToDatabase;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'pdo_mysql';
    }
}
