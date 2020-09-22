<?php

namespace Flat3\OData\Tests\Unit\Parser;

use Flat3\OData\Drivers\Database\Store;
use Flat3\OData\EntityType\Collection;
use Flat3\OData\Exception\Internal\ParserException;
use Flat3\OData\Expression\Parser\Filter;
use Flat3\OData\Expression\Parser\Search;
use Flat3\OData\Property;
use Flat3\OData\Tests\LoopbackEntitySet;
use Flat3\OData\Tests\TestCase;
use Flat3\OData\Transaction;
use Flat3\OData\Type\Int32;
use Illuminate\Http\Request;

class ParserTest extends TestCase
{
    public $filter_tests = [
        "title eq 'te''st'" => "( title eq 'te''st' )",
        "title eq 'test'" => "( title eq 'test' )",
        "title eq 'test" => "Encountered an invalid symbol at: title eq 'test<EOF",
        "id eq 4" => "( id eq 4 )",
        "id gt 4" => "( id gt 4 )",
        "id lt 4" => "( id lt 4 )",
        "id ge 4" => "( id ge 4 )",
        "id le 4" => "( id le 4 )",
        "id eq test" => "Encountered an invalid symbol at: id eq >t<est",
        "title in ('a', 'b', 'c')" => "title in ( 'a' , 'b' , 'c' )",
        "title in ('a')" => "title in ( 'a' )",
        "id in (4, 3)" => "id in ( 4 , 3 )",
        "id lt 4 and id gt 2" => "( ( id lt 4 ) and ( id gt 2 ) )",
        "id lt 4 or id gt 2" => "( ( id lt 4 ) or ( id gt 2 ) )",
        "id lt 4 or id lt 3 or id lt 2" => "( ( ( id lt 4 ) or ( id lt 3 ) ) or ( id lt 2 ) )",
        "id lt 4 or id lt 3 and id lt 2" => "( ( id lt 4 ) or ( ( id lt 3 ) and ( id lt 2 ) ) )",
        "id lt 4 or id in (3, 1) and id ge 2" => "( ( id lt 4 ) or ( id in ( 3 , 1 ) and ( id ge 2 ) ) )",
        "(id lt 4 and (id ge 7 or id gt 3)" => "Expression has unbalanced parentheses",
        "(id lt 4 a" => "Encountered an invalid symbol at: (id lt 4 >a<",
        "(id lt 4 and id ge 7) or id gt 3" => "( ( ( id lt 4 ) and ( id ge 7 ) ) or ( id gt 3 ) )",
        "id lt 4 or (id gt 3 and id gt 2)" => "( ( id lt 4 ) or ( ( id gt 3 ) and ( id gt 2 ) ) )",
        "(id lt 4 and id ge 7) or (id gt 3 and id gt 2)" => "( ( ( id lt 4 ) and ( id ge 7 ) ) or ( ( id gt 3 ) and ( id gt 2 ) ) )",
        "id add 3.14 eq 1.59" => "( ( id add 3.14 ) eq 1.59 )",
        "id in (1.59, 2.14)" => "id in ( 1.59 , 2.14 )",
        "(id add 3.14) in (1.59, 2.14) or (id gt -2.40 and id gt 4 add 5)" => "( ( id add 3.14 ) in ( 1.59 , 2.14 ) or ( ( id gt -2.4 ) and ( id gt ( 4 add 5 ) ) ) )",
        "id add 3.14 add 5 in (1.59, 2.14)" => "( ( id add 3.14 ) add 5 in ( 1.59 , 2.14 ) )",
        "id add 3.14 in (1.59, 2.14)" => "( id add 3.14 in ( 1.59 , 2.14 ) )",
        "id add 3.14 in (1.59, 2.14) or (id gt -2.40 and id gt 4 add 5)" => "( ( id add 3.14 in ( 1.59 , 2.14 ) ) or ( ( id gt -2.4 ) and ( id gt ( 4 add 5 ) ) ) )",
        "not(contains(title,'a')) and ((title eq 'abcd') or (title eq 'e'))" => "( ( not contains( title , 'a' ) ) and ( ( title eq 'abcd' ) or ( title eq 'e' ) ) )",
        "not(title eq 'a')" => "( not ( title eq 'a' ) )",
        "title eq 'b' and not(title eq 'a')" => "( ( title eq 'b' ) and ( not ( title eq 'a' ) ) )",
        "title eq 'b' or not(title eq 'a')" => "( ( title eq 'b' ) or ( not ( title eq 'a' ) ) )",
        "contains(title, 'b')" => "contains( title , 'b' )",
        "endswith(title, 'b')" => "endswith( title , 'b' )",
        "concat(title, 'abc') eq '123abc'" => "( concat( title , 'abc' ) eq '123abc' )",
        "concat(title, 'abc', 4.0) eq '123abc'" => "The concat function requires 2 arguments",
        "concat(title, id) eq '123abc'" => "( concat( title , id ) eq '123abc' )",
        "concat(title, concat(id, 4)) eq '123abc'" => "( concat( title , concat( id , 4 ) ) eq '123abc' )",
        "indexof(title,'abc123') eq 1" => "( indexof( title , 'abc123' ) eq 1 )",
        "length(title) eq 1" => "( length( title ) eq 1 )",
        "substring(title,1) eq 'abc123'" => "( substring( title , 1 ) eq 'abc123' )",
        "substring(title,1,4) eq 'abc123'" => "( substring( title , 1 , 4 ) eq 'abc123' )",
        "matchesPattern(title,'%5EA.*e$')" => "matchesPattern( title , '^A.*e$' )",
        "tolower(title) eq 'abc123'" => "( tolower( title ) eq 'abc123' )",
        "toupper(title) eq 'abc123'" => "( toupper( title ) eq 'abc123' )",
        "trim(title) eq 'abc123'" => "( trim( title ) eq 'abc123' )",
        "ceiling(title) eq 4" => "( ceiling( title ) eq 4 )",
        "floor(title) eq 4" => "( floor( title ) eq 4 )",
        "round(title) eq 4" => "( round( title ) eq 4 )",
    ];

    public $search_tests = [
        't1' => "'t1'",
        't1 OR t2' => "( 't1' OR 't2' )",
        't1 OR t2 OR t3' => "( ( 't1' OR 't2' ) OR 't3' )",
        't1 OR t2 AND t3' => "( 't1' OR ( 't2' AND 't3' ) )",
        't1 OR t2 NOT t3 AND t4' => "( 't2' OR ( ( NOT 't3' ) AND 't4' ) )",
        "'a t1' OR t1" => "( 'a t1' OR 't1' )",
        "'a ''t1' OR t1" => "( 'a ''t1' OR 't1' )",
        '( t1 OR t2 ) AND t3' => "( ( 't1' OR 't2' ) AND 't3' )",
        '(t1 OR (t2 AND t3))' => "( 't1' OR ( 't2' AND 't3' ) )",
    ];

    public function test_search_parser()
    {
        foreach (array_reverse($this->search_tests) as $from => $to) {
            try {
                $type = new Collection('test');
                $k = new Property('id', Int32::type());
                $type->setKey($k);
                $transaction = new Transaction();
                $transaction->setRequest(new Request());
                $query = new LoopbackEntitySet(new Store('test', $type), $transaction);

                $parser = new Search($query);

                $tree = $parser->generateTree(urldecode($from));
                $tree->compute();

                $this->assertEquals($to, trim($query->searchBuffer));
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

    public function test_filter_parser()
    {
        foreach (array_reverse($this->filter_tests) as $from => $to) {
            try {
                $type = new Collection('test');
                $k = new Property('id', Int32::type());
                $type->setKey($k);
                $transaction = new Transaction();
                $transaction->setRequest(new Request());
                $query = new LoopbackEntitySet(new Store('test', $type), $transaction);

                $parser = new Filter($query);
                $parser->addValidLiteral('id');
                $parser->addValidLiteral('title');

                $tree = $parser->generateTree(urldecode($from));
                $tree->compute();

                // Success, validate output
                $this->assertEquals($to, trim($query->filterBuffer));
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
