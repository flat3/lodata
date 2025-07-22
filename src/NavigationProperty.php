<?php

declare(strict_types=1);

namespace Flat3\Lodata;

use Flat3\Lodata\Controller\Request;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\ConfigurationException;
use Flat3\Lodata\Exception\Protocol\NotFoundException;
use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Interfaces\EntitySet\ReadInterface;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Transaction\NavigationRequest;
use Illuminate\Support\Str;

/**
 * Navigation Property
 * @link https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530365
 * @package Flat3\Lodata
 */
class NavigationProperty extends Property
{
    const identifier = 'Edm.NavigationPropertyPath';

    /**
     * The partner property referring back to this property
     * @var self $partner
     */
    protected $partner;

    /**
     * The referential constraints attached to this property
     * @var ObjectArray $constraints
     */
    protected $constraints;

    /**
     * Whether the target of this navigation property refers to a collection
     * @var bool $collection
     */
    protected $collection = false;

    /**
     * Whether this navigation property can be used as an expand request
     * @var bool $expandable
     */
    protected $expandable = true;

    public function __construct($name, EntityType $entityType)
    {
        if (!$entityType->hasKey()) {
            throw new ConfigurationException(
                'missing_entity_type_key',
                'The specified entity type must have a key defined'
            );
        }

        if ($name instanceof IdentifierInterface) {
            $name = $name->getName();
        }

        parent::__construct($name, $entityType);

        $this->constraints = new ObjectArray();
    }

    /**
     * Get whether this navigation property represents a collection
     * @return bool
     */
    public function isCollection(): bool
    {
        return $this->collection;
    }

    /**
     * Set whether this navigation property represents a collection
     * @param  bool  $collection
     * @return $this
     */
    public function setCollection(bool $collection): self
    {
        $this->collection = $collection;
        return $this;
    }

    /**
     * Get whether this property can be expanded
     * @return bool
     */
    public function isExpandable(): bool
    {
        return $this->expandable;
    }

    /**
     * Set whether this property can be expanded
     * @param  bool  $expandable
     * @return $this
     */
    public function setExpandable(bool $expandable): self
    {
        $this->expandable = $expandable;

        return $this;
    }

    /**
     * Get the partner navigation property of this property
     * @return $this|null
     */
    public function getPartner(): ?self
    {
        return $this->partner;
    }

    /**
     * Set the partner navigation property of this property
     * @param  NavigationProperty  $partner  Partner
     * @return $this
     */
    public function setPartner(self $partner): self
    {
        $this->partner = $partner;

        return $this;
    }

    /**
     * Add a referential constraint of this property
     * @param  ReferentialConstraint  $constraint  Referential constraint
     * @return $this
     */
    public function addConstraint(ReferentialConstraint $constraint): self
    {
        $this->constraints[] = $constraint;

        return $this;
    }

    /**
     * Get the referential constraints attached to this property
     * @return ObjectArray|ReferentialConstraint[] Referential constraints
     */
    public function getConstraints(): ObjectArray
    {
        return $this->constraints;
    }

    /**
     * Generate a property value from this property
     * @param  Transaction  $transaction  Related transaction
     * @param  NavigationRequest  $navigationRequest  Navigation request
     * @param  ComplexValue  $value  Entity this property is attached to
     * @return PropertyValue|null Property value
     */
    public function generatePropertyValue(
        Transaction $transaction,
        NavigationRequest $navigationRequest,
        ComplexValue $value
    ): ?PropertyValue {
        $expansionTransaction = clone $transaction;
        $expansionTransaction->setRequest($navigationRequest);

        $propertyValue = $value->newPropertyValue();
        $propertyValue->setProperty($this);

        /** @var NavigationBinding $binding */
        $binding = $value->getEntitySet()->getBindingByNavigationProperty($this);
        $targetEntitySet = $binding->getTarget();

        $expansionSet = clone $targetEntitySet;
        $expansionSet->setTransaction($expansionTransaction);
        $expansionSet->setNavigationSource($propertyValue);

        $requestedTarget = null;
        if ($expansionSet instanceof ReadInterface && $target = $this->requestedTargetId($expansionSet, $navigationRequest, $transaction)) {
            try {
                $id = EntitySet::idToKeyProperty($target, $expansionSet, $transaction);
                $requestedTarget = $expansionSet->read($id);
            } catch (NotFoundException $e) {
                // ignore
            }
        }

        if ($requestedTarget) {
            $propertyValue->setValue($requestedTarget);
        } elseif ($this->isCollection()) {
            $propertyValue->setValue($expansionSet);
        } else {
            $expansionSingular = $expansionSet->query()->current();
            $propertyValue->setValue($expansionSingular);
        }

        $value->addPropertyValue($propertyValue);

        return $propertyValue;
    }

    protected function requestedTargetId(EntitySet $targetSet, NavigationRequest $navigationRequest, Transaction $transaction): ?string
    {
        if (!$targetSet instanceof ReadInterface) {
            return null;
        }

        if ($params = $navigationRequest->getNavigationParameters()) {
            return $params;
        }

        $qualifiedId = $transaction->getQueryParam('id');

        if (!$qualifiedId && $transaction->getMethod() !== Request::METHOD_GET) {
            try {
                $body = $transaction->getBodyAsArray();
            } catch (\Throwable $e) {
                $body = [];
            }
            if (isset($body['@odata.id'])) {
                $qualifiedId = $body['@odata.id'];
            }
        }

        if (!$qualifiedId) {
            return null;
        }

        $lexer = new Lexer(Str::after((string) $qualifiedId, app(Endpoint::class)->route() . '/'));
        $entity = $lexer->identifier();
        if ($entity !== $targetSet->getName()) {
            throw new BadRequestException(
                'navigation_reference_invalid',
                'Navigation reference entity type is invalid'
            );
        }

        return $lexer->maybeMatchingParenthesis();
    }
}
