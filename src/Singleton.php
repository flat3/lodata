<?php

declare(strict_types=1);

namespace Flat3\Lodata;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\LexerException;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\Gate;
use Flat3\Lodata\Interfaces\AnnotationInterface;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\ServiceInterface;
use Flat3\Lodata\Traits\HasAnnotations;
use Flat3\Lodata\Traits\HasIdentifier;
use Flat3\Lodata\Traits\HasTitle;
use Flat3\Lodata\Transaction\MetadataContainer;

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
        $url = $transaction->getContextUrl().'#'.$this->getName();

        $properties = $transaction->getProjectedProperties();

        if ($properties) {
            $url .= sprintf('(%s)', join(',', $properties));
        }

        return $url;
    }

    /**
     * @param  Transaction  $transaction
     * @return MetadataContainer
     */
    protected function getMetadata(Transaction $transaction): MetadataContainer
    {
        $metadata = parent::getMetadata($transaction);

        if (!$this->usesReferences()) {
            $metadata['readLink'] = $this->getResourceUrl($transaction);
        }

        return $metadata;
    }

    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): ?PipeInterface {
        $lexer = new Lexer($currentSegment);

        try {
            $identifier = $lexer->qualifiedIdentifier();
        } catch (LexerException $e) {
            throw new PathNotHandledException();
        }

        $singleton = Lodata::getSingleton($identifier);

        if (!$singleton instanceof Singleton || !$singleton->getIdentifier()->matchesNamespace($identifier)) {
            throw new PathNotHandledException();
        }

        Gate::read($singleton, $transaction)->ensure();

        return $singleton;
    }
}
