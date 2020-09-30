<?php

namespace Flat3\OData\Tests\Unit\Parser;

use Flat3\OData\Drivers\Database\MySQL\EntitySet;
use Flat3\OData\Drivers\Database\Store;
use Flat3\OData\EntityType\Collection;
use Flat3\OData\Exception\Internal\ParserException;
use Flat3\OData\Property;
use Flat3\OData\Tests\TestCase;
use Flat3\OData\Transaction;
use Flat3\OData\Type\EntityType;
use Flat3\OData\Type\Int32;
use Flat3\OData\Type\String_;
use Illuminate\Http\Request;

class ParserMySQLTest extends TestCase
{
    public $tests = [
        'title eq "test"' => 'Encountered an invalid symbol at: title eq >"<test"',
        "title eq 'test'" => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( test.`title` = ? )',
            [
                'test',
            ],
        ],
        "title eq 'test" => 'Encountered an invalid symbol at: title eq \'test<EOF',
        'id eq 4' => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( test.`id` = ? )',
            [
                4,
            ],
        ],
        'id gt 4' => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( test.`id` > ? )',
            [
                4,
            ],
        ],
        'id lt 4' => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( test.`id` < ? )',
            [
                4,
            ],
        ],
        'id ge 4' => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( test.`id` >= ? )',
            [
                4,
            ],
        ],
        'id le 4' => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( test.`id` <= ? )',
            [
                4,
            ],
        ],
        'id eq test' => 'Encountered an invalid symbol at: id eq >t<est',
        "title in ('a', 'b', 'c')" => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE test.`title` IN ( ? , ? , ? )',
            [
                'a',
                'b',
                'c',
            ],
        ],
        "title in ('a')" => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE test.`title` IN ( ? )',
            [
                'a',
            ],
        ],
        'id in (4, 3)' => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE test.`id` IN ( ? , ? )',
            [
                4,
                3,
            ],
        ],
        'id lt 4 and id gt 2' => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( ( test.`id` < ? ) AND ( test.`id` > ? ) )',
            [
                4,
                2,
            ],
        ],
        'id lt 4 or id gt 2' => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( ( test.`id` < ? ) OR ( test.`id` > ? ) )',
            [
                4,
                2,
            ],
        ],
        'id lt 4 or id lt 3 or id lt 2' => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( ( ( test.`id` < ? ) OR ( test.`id` < ? ) ) OR ( test.`id` < ? ) )',
            [
                4,
                3,
                2,
            ],
        ],
        'id lt 4 or id lt 3 and id lt 2' => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( ( test.`id` < ? ) OR ( ( test.`id` < ? ) AND ( test.`id` < ? ) ) )',
            [
                4,
                3,
                2,
            ],
        ],
        'id lt 4 or id in (3, 1) and id ge 2' => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( ( test.`id` < ? ) OR ( test.`id` IN ( ? , ? ) AND ( test.`id` >= ? ) ) )',
            [
                4,
                3,
                1,
                2,
            ],
        ],
        '(id lt 4 and (id ge 7 or id gt 3)' => 'Expression has unbalanced parentheses',
        '(id lt 4 a' => 'Encountered an invalid symbol at: (id lt 4 >a<',
        '(id lt 4 and id ge 7) or id gt 3' => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( ( ( test.`id` < ? ) AND ( test.`id` >= ? ) ) OR ( test.`id` > ? ) )',
            [
                4,
                7,
                3,
            ],
        ],
        'id lt 4 or (id gt 3 and id gt 2)' => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( ( test.`id` < ? ) OR ( ( test.`id` > ? ) AND ( test.`id` > ? ) ) )',
            [
                4,
                3,
                2,
            ],
        ],
        '(id lt 4 and id ge 7) or (id gt 3 and id gt 2)' => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( ( ( test.`id` < ? ) AND ( test.`id` >= ? ) ) OR ( ( test.`id` > ? ) AND ( test.`id` > ? ) ) )',
            [
                4,
                7,
                3,
                2,
            ],
        ],
        'id add 3.14 eq 1.59' => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( ( test.`id` + ? ) = ? )',
            [
                3.14,
                1.59,
            ],
        ],
        'id in (1.59, 2.14)' => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE test.`id` IN ( ? , ? )',
            [
                1.59,
                2.14,
            ],
        ],
        '(id add 3.14) in (1.59, 2.14) or (id gt -2.40 and id gt 4 add 5)' => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( ( test.`id` + ? ) IN ( ? , ? ) OR ( ( test.`id` > ? ) AND ( test.`id` > ( ? + ? ) ) ) )',
            [
                3.14,
                1.59,
                2.14,
                -2.4,
                4,
                5,
            ],
        ],
        'id add 3.14 add 5 in (1.59, 2.14)' => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( ( test.`id` + ? ) + ? IN ( ? , ? ) )',
            [
                3.14,
                5,
                1.59,
                2.14,
            ],
        ],
        'id add 3.14 in (1.59, 2.14)' => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( test.`id` + ? IN ( ? , ? ) )',
            [
                3.14,
                1.59,
                2.14,
            ],
        ],
        'id add 3.14 in (1.59, 2.14) or (id gt -2.40 and id gt 4 add 5)' => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( ( test.`id` + ? IN ( ? , ? ) ) OR ( ( test.`id` > ? ) AND ( test.`id` > ( ? + ? ) ) ) )',
            [
                3.14,
                1.59,
                2.14,
                -2.4,
                4,
                5,
            ],
        ],
        "not(contains(title,'a')) and ((title eq 'abcd') or (title eq 'e'))" => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( ( NOT test.`title` LIKE ? ) AND ( ( test.`title` = ? ) OR ( test.`title` = ? ) ) )',
            [
                '%a%',
                'abcd',
                'e',
            ],
        ],
        "not(title eq 'a')" => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( NOT ( test.`title` = ? ) )',
            [
                'a',
            ],
        ],
        "title eq 'b' and not(title eq 'a')" => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( ( test.`title` = ? ) AND ( NOT ( test.`title` = ? ) ) )',
            [
                'b',
                'a',
            ],
        ],
        "title eq 'b' or not(title eq 'a')" => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( ( test.`title` = ? ) OR ( NOT ( test.`title` = ? ) ) )',
            [
                'b',
                'a',
            ],
        ],
        "contains(title, 'b')" => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE test.`title` LIKE ?',
            [
                '%b%',
            ],
        ],
        "endswith(title, 'b')" => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE test.`title` LIKE ?',
            [
                '%b',
            ],
        ],
        "concat(title, 'abc') eq '123abc'" => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( CONCAT( test.`title` , ? ) = ? )',
            [
                'abc',
                '123abc',
            ],
        ],
        "concat(title, 'abc', 4.0) eq '123abc'" => 'The concat function requires 2 arguments',
        "concat(title, id) eq '123abc'" => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( CONCAT( test.`title` , test.`id` ) = ? )',
            [
                '123abc',
            ],
        ],
        "concat(title, concat(id, 4)) eq '123abc'" => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( CONCAT( test.`title` , CONCAT( test.`id` , ? ) ) = ? )',
            [
                4,
                '123abc',
            ],
        ],
        "indexof(title,'abc123') eq 1" => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( INSTR( test.`title` , ? ) = ? )',
            [
                'abc123',
                1,
            ],
        ],
        'length(title) eq 1' => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( LENGTH( test.`title` ) = ? )',
            [
                1,
            ],
        ],
        "substring(title,1) eq 'abc123'" => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( SUBSTRING( test.`title` , ? ) = ? )',
            [
                1,
                'abc123',
            ],
        ],
        "substring(title,1,4) eq 'abc123'" => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( SUBSTRING( test.`title` , ? , ? ) = ? )',
            [
                1,
                4,
                'abc123',
            ],
        ],
        "matchesPattern(title,'^A.*e\$')" => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE REGEXP_LIKE( test.`title` , ? )',
            [
                '^A.*e$',
            ],
        ],
        "tolower(title) eq 'abc123'" => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( LOWER( test.`title` ) = ? )',
            [
                'abc123',
            ],
        ],
        "toupper(title) eq 'abc123'" => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( UPPER( test.`title` ) = ? )',
            [
                'abc123',
            ],
        ],
        "trim(title) eq 'abc123'" => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( TRIM( test.`title` ) = ? )',
            [
                'abc123',
            ],
        ],
        'ceiling(title) eq 4' => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( CEILING( test.`title` ) = ? )',
            [
                4,
            ],
        ],
        'floor(title) eq 4' => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( FLOOR( test.`title` ) = ? )',
            [
                4,
            ],
        ],
        'round(title) eq 4' => [
            'SELECT test.`id` AS id, test.`title` AS title FROM test WHERE ( ROUND( test.`title` ) = ? )',
            [
                4,
            ],
        ],
    ];

    public function test_parser()
    {
        foreach (array_reverse($this->tests) as $from => $to) {
            try {
                $entity_type = new class('test') extends EntityType{};
                $id = new Property\Declared('id', Int32::class);
                $id->setFilterable(true);
                $entity_type->setKey($id);
                $title = new Property\Declared('title', String_::class);
                $title->setFilterable(true);
                $entity_type->addProperty($title);
                $store = new Store('test', $entity_type);

                $transaction = new Transaction();
                $request = new Request();
                $request->query->set('$filter', $from);
                $request->query->set('$select', 'id,title');
                $transaction->setRequest($request);

                $query = new EntitySet($store, $transaction);

                $q = $query->getSetResultQueryString();
                $v = $query->getParameters();

                // Success, validate output
                $this->assertMatchesTextSnapshot(json_encode([$from, $q, $v], JSON_PRETTY_PRINT));
                //$this->assertEquals($to, array($q, $v), sprintf('%s => %s / %s', $from, $q, implode(',', $v)));
            } catch (ParserException $e) {
                // Threw an unexpected exception
                if (!is_string($to)) {
                    throw $e;
                }

                // Threw an expected exception
                $this->assertEquals($to, $e->getMessage());
                continue;
            }

            // Validate that an exception should not have thrown
            $this->assertNotNull($to);
        }
    }
}
