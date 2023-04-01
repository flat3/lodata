<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Helpers;

use MongoDB\Collection;
use MongoDB\InsertOneResult;

class MockMongoCollection extends Collection
{
    public function findOne($filter = [], array $options = [])
    {
        return $this->rewrite(parent::findOne($filter, $options));
    }

    public function insertOne($document, array $options = [])
    {
        $result = parent::insertOne($document, $options);

        return new InsertOneResult((new Invader($result))->writeResult, $this->rewrite($document)['_id']);
    }

    public function find($filter = [], array $options = [])
    {
        $results = parent::find($filter, $options);
        $nResults = [];
        foreach ($results as $result) {
            $nResults[] = $this->rewrite($result);
        }
        return $nResults;
    }

    protected function rewrite($record)
    {
        if (null === $record) {
            return $record;
        }

        $record['_id'] = strtolower($record['name']);

        return $record;
    }
}