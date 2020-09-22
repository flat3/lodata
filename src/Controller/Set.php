<?php

namespace Flat3\OData\Controller;

use Flat3\OData\DataModel;
use Flat3\OData\Exception\NotImplementedException;
use Flat3\OData\Exception\PathNotHandledException;
use Flat3\OData\Expression\Lexer;
use Flat3\OData\Option;
use Flat3\OData\Store;
use Flat3\OData\Transaction;

class Set extends Handler
{
    public const path = parent::path.Lexer::ODATA_IDENTIFIER;

    /** @var Store $store */
    protected $store;

    public function setup(Transaction $transaction): void
    {
        parent::setup($transaction);

        $identifier = array_shift($this->pathComponents);

        /** @var DataModel $data_model */
        $data_model = app()->make(DataModel::class);
        $lexer = new Lexer($identifier);
        $store = $data_model->getResources()->get($lexer->odataIdentifier());

        if (!$store instanceof Store) {
            throw new PathNotHandledException();
        }

        $this->store = $store;

        // Validate expand properties
        $expand = $transaction->getExpand();
        $expand->getExpansionRequests($store->getEntityType());
    }

    public function handle(): void
    {
        $transaction = $this->transaction;
        $store = $this->store;
        $transaction->setContentTypeJson();

        foreach (
            [
                $transaction->getCount(), $transaction->getFilter(), $transaction->getOrderBy(),
                $transaction->getSearch(), $transaction->getSkip(), $transaction->getTop(),
            ] as $sqo
        ) {
            /** @var Option $sqo */
            if ($sqo->hasValue() && !in_array(get_class($sqo), $store->getSupportedQueryOptions(), true)) {
                throw new NotImplementedException(
                    sprintf('The %s system query option is not supported by this entity set', $sqo::param)
                );
            }
        }

        $maxPageSize = $transaction->getPreference('maxpagesize');
        $top = $transaction->getTop();
        if (!$top->hasValue() && $maxPageSize) {
            $top->setValue($maxPageSize);
        }

        $setCount = $store->getCount($transaction);

        $metadata = ['context' => $transaction->getEntityCollectionContextUrl($store)];

        $count = $transaction->getCount();
        if (true === $count->getValue()) {
            $metadata['count'] = $setCount;
        }

        $skip = $transaction->getSkip();

        if ($top->hasValue()) {
            if ($top->getValue() + ($skip->getValue() ?: 0) < $setCount) {
                $np = $transaction->getQueryParams();
                $np['$skip'] = $top->getValue() + ($skip->getValue() ?: 0);
                $metadata['nextLink'] = $transaction->getEntityCollectionResourceUrl($store).'?'.http_build_query(
                        $np,
                        null,
                        '&',
                        PHP_QUERY_RFC3986
                    );
            }
        }

        $metadata = $transaction->getMetadata()->filter($metadata);

        $transaction->getResponse()->setCallback(function () use ($transaction, $metadata) {
            $entitySet = $this->store->getEntitySet($transaction);

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
