<?php

namespace Flat3\OData\Controller;

use Flat3\OData\Exception\Internal\PathNotHandledException;
use Flat3\OData\Expression\Lexer;
use Flat3\OData\ODataModel;
use Flat3\OData\Resource\EntitySet;
use Flat3\OData\Transaction;
use Illuminate\Contracts\Container\BindingResolutionException;

class Set extends Handler
{
    public const path = parent::path.Lexer::ODATA_IDENTIFIER;

    /** @var EntitySet $entitySet */
    protected $entitySet;

    /**
     * @param  Transaction  $transaction
     * @throws BindingResolutionException
     */
    public function setup(Transaction $transaction): void
    {
        parent::setup($transaction);

        $identifier = array_shift($this->pathComponents);

        /** @var ODataModel $data_model */
        $data_model = app()->make(ODataModel::class);
        $lexer = new Lexer($identifier);
        $entitySet = $data_model->getResources()->get($lexer->odataIdentifier());

        if (!$entitySet instanceof EntitySet) {
            throw new PathNotHandledException();
        }

        $this->entitySet = $entitySet;

        // Validate $expand
        $expand = $transaction->getExpand();
        $expand->getExpansionRequests($entitySet->getType());

        // Validate $select
        $select = $transaction->getSelect();
        $select->getSelectedProperties($entitySet);

        // Validate $orderby
        $orderby = $transaction->getOrderBy();
        $orderby->getSortOrders($entitySet);
    }

    public function handle(): void
    {
        $transaction = $this->transaction;
        $entitySet = $this->entitySet->withTransaction($transaction);
        $transaction->setContentTypeJson();

        $maxPageSize = $transaction->getPreference('maxpagesize');
        $top = $transaction->getTop();
        if (!$top->hasValue() && $maxPageSize) {
            $transaction->preferenceApplied('maxpagesize', $maxPageSize);
            $top->setValue($maxPageSize);
        }

        $setCount = $entitySet->count();

        $metadata = [];

        $select = $transaction->getSelect();

        if ($select->hasValue() && !$select->isStar()) {
            $metadata['context'] = $transaction->getCollectionOfProjectedEntitiesContextUrl(
                $entitySet,
                $select->getValue()
            );
        } else {
            $metadata['context'] = $transaction->getCollectionOfEntitiesContextUrl($entitySet);
        }

        $count = $transaction->getCount();
        if (true === $count->getValue()) {
            $metadata['count'] = $setCount;
        }

        $skip = $transaction->getSkip();

        if ($top->hasValue()) {
            if ($top->getValue() + ($skip->getValue() ?: 0) < $setCount) {
                $np = $transaction->getQueryParams();
                $np['$skip'] = $top->getValue() + ($skip->getValue() ?: 0);
                $metadata['nextLink'] = $transaction->getEntityCollectionResourceUrl($entitySet).'?'.http_build_query(
                        $np,
                        null,
                        '&',
                        PHP_QUERY_RFC3986
                    );
            }
        }

        $metadata = $transaction->getMetadata()->filter($metadata);

        $transaction->getResponse()->setCallback(function () use ($transaction, $metadata) {
            $entitySet = $this->entitySet->withTransaction($transaction);

            $transaction->outputJsonObjectStart();

            if ($metadata) {
                $transaction->outputJsonKV($metadata);
                $transaction->outputJsonSeparator();
            }

            $transaction->outputJsonKey('value');
            $transaction->outputJsonArrayStart();
            $entitySet->writeToResponse($transaction);
            $transaction->outputJsonArrayEnd();
            $transaction->outputJsonObjectEnd();
        });
    }
}
