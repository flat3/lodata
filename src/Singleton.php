<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\Gate;
use Flat3\Lodata\Interfaces\AnnotationInterface;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\ServiceInterface;
use Flat3\Lodata\Traits\HasAnnotations;
use Flat3\Lodata\Traits\HasIdentifier;
use Flat3\Lodata\Traits\HasTitle;

/**
 * Singleton
 * @link https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530395
 * @package Flat3\Lodata
 */
class Singleton extends Entity implements ServiceInterface, IdentifierInterface, AnnotationInterface
{
    use HasIdentifier;
    use HasTitle;
    use HasAnnotations;

    public function __construct(string $identifier, EntityType $type)
    {
        parent::__construct();
        $this->setIdentifier($identifier);
        $this->setType($type);
    }

    /**
     * Get the OData kind of this resource
     * @return string Kind
     */
    public function getKind(): string
    {
        return 'Singleton';
    }

    /**
     * Get the resource URL of this singleton
     * @param  Transaction  $transaction  Related transaction
     * @return string Resource URL
     */
    public function getResourceUrl(Transaction $transaction): string
    {
        return $transaction->getResourceUrl().$this->getName();
    }

    /**
     * Get the context URL of this singleton
     * @param  Transaction  $transaction  Related transaction
     * @return string Context URL
     */
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
