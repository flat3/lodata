<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\Gate;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\ServiceInterface;
use Flat3\Lodata\Traits\HasIdentifier;
use Flat3\Lodata\Traits\HasTitle;

class Singleton extends Entity implements ServiceInterface, IdentifierInterface
{
    use HasIdentifier;
    use HasTitle;

    public function __construct(string $identifier, EntityType $type)
    {
        parent::__construct();
        $this->setIdentifier($identifier);
        $this->setType($type);
    }

    public function getKind(): string
    {
        return 'Singleton';
    }

    public function getResourceUrl(Transaction $transaction): string
    {
        return $transaction->getResourceUrl().$this->getName();
    }

    public function getContextUrl(Transaction $transaction): string
    {
        return $transaction->getContextUrl().'#'.$this->getIdentifier();
    }

    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): ?PipeInterface {
        $singleton = Lodata::getSingleton($currentSegment);

        if (!$singleton instanceof Singleton) {
            throw new PathNotHandledException();
        }

        Gate::check(Gate::READ, $singleton, $transaction);

        return $singleton;
    }
}
