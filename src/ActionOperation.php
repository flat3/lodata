<?php

namespace Flat3\OData;

use Flat3\OData\Controller\Transaction;
use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Interfaces\PipeInterface;
use Flat3\OData\PathComponent\Operation;

class ActionOperation extends Operation
{
    public function getKind(): string
    {
        return 'Action';
    }

    public static function pipe(
        Transaction $transaction,
        string $currentComponent,
        ?string $nextComponent,
        ?PipeInterface $argument
    ): ?PipeInterface {
        if ($nextComponent) {
            throw new BadRequestException(
                'cannot_compose_action',
                'It is not permitted to further compose the result of an action'
            );
        }

        return parent::pipe(
            $transaction,
            $currentComponent,
            $nextComponent,
            $argument
        );
    }
}
