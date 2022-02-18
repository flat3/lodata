<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Client;

use Flat3\Lodata\Client\Collection;
use Flat3\Lodata\Exception\Protocol\ProtocolException;
use Flat3\Lodata\Tests\Drivers\WithNumericCollectionDriver;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Testing\TestResponse;

class CollectionTest extends TestCase
{
    use WithNumericCollectionDriver;

    /** @var Collection $collection */
    protected $collection;

    public function setUp(): void
    {
        parent::setUp();

        $this->collection = new Collection('http://localhost/odata/passengers');

        Http::fake(function (HttpRequest $request) {
            $uri = $request->toPsrRequest()->getUri();
            $testRequest = new Request();
            $testRequest->path($uri->getPath(), false);
            $testRequest->headers($request->headers());
            $testRequest->body($request->body());
            parse_str($uri->getQuery(), $query);
            foreach ($query as $key => $value) {
                $testRequest->query($key, $value);
            }

            try {
                $response = $this->req($testRequest);
            } catch (ProtocolException $e) {
                $response = new TestResponse($e->toResponse());
            }

            return Http::response(
                $this->getResponseContent($response),
                $response->getStatusCode(),
                $response->headers->all()
            );
        });
    }

    public function test_all()
    {
        $this->assertMatchesSnapshot($this->collection->all());
    }

    public function test_median()
    {
        $this->assertEquals(2.7, $this->collection->median('age'));
    }

    public function test_mode()
    {
        $this->assertEquals([2], $this->collection->mode('age'));
    }

    public function test_avg()
    {
        $this->assertEquals(2.85, $this->collection->avg('age'));
    }

    public function test_contains_eq()
    {
        $this->assertTrue($this->collection->contains('name', '=', 'Beta'));
    }

    public function test_contains_eq_not()
    {
        $this->assertFalse($this->collection->contains('name', '=', 'Oofle'));
    }

    public function test_contains_gt()
    {
        $this->assertTrue($this->collection->contains('age', '<', 3));
    }

    public function test_contains_gt_not()
    {
        $this->assertFalse($this->collection->contains('age', '>', 99));
    }

    public function test_isempty()
    {
        $this->assertFalse($this->collection->isEmpty());
    }

    public function test_isnotempty()
    {
        $this->assertTrue($this->collection->isNotEmpty());
    }

    public function test_skip()
    {
        $this->assertMatchesSnapshot($this->collection->skip(1));
    }

    public function test_take()
    {
        $this->assertMatchesSnapshot($this->collection->take(1));
    }

    public function test_skip_take()
    {
        $this->assertMatchesSnapshot($this->collection->skip(1)->take(1));
    }

    public function test_filter()
    {
        $this->assertMatchesSnapshot($this->collection->filter("name eq 'Alpha'"));
    }

    public function test_filter_callback()
    {
        $this->assertMatchesSnapshot($this->collection->filter(function ($entity) {
            return $entity['name'] === 'Alpha';
        }));
    }

    public function test_sortby()
    {
        $this->assertMatchesSnapshot($this->collection->sortBy('name'));
    }

    public function test_sortby_desc()
    {
        $this->assertMatchesSnapshot($this->collection->sortBy('name', SORT_REGULAR, true));
    }

    public function test_filter_sort()
    {
        $this->assertMatchesSnapshot($this->collection->filter("age gt 2")->sortBy('in_role'));
    }

    public function test_chunk()
    {
        $this->assertMatchesSnapshot($this->collection->chunk(2));
    }

    public function test_get()
    {
        $this->assertMatchesSnapshot($this->collection->get(2));
    }

    public function test_get_missing()
    {
        $this->assertMatchesSnapshot($this->collection->get(99));
    }

    public function test_get_missing_default_value()
    {
        $this->assertMatchesSnapshot($this->collection->get(99, 'hello'));
    }

    public function test_get_missing_default_callback()
    {
        $this->assertMatchesSnapshot($this->collection->get(99, function () {
            return 'hello';
        }));
    }

    public function test_has()
    {
        $this->assertTrue($this->collection->has(2));
    }

    public function test_has_not()
    {
        $this->assertFalse($this->collection->has(99));
    }

    public function test_forpage()
    {
        $this->assertMatchesSnapshot($this->collection->forPage(2, 2)->all());
    }

    public function test_values()
    {
        $this->assertMatchesSnapshot($this->collection->values());
    }

    public function test_slice()
    {
        $this->assertMatchesSnapshot($this->collection->slice(1));
    }

    public function test_slice_length()
    {
        $this->assertMatchesSnapshot($this->collection->slice(1, 1));
    }

    public function test_count()
    {
        $this->assertEquals(5, $this->collection->count());
    }

    public function test_first()
    {
        $this->assertMatchesSnapshot($this->collection->first());
    }

    public function test_last()
    {
        $this->assertMatchesSnapshot($this->collection->last());
    }

    public function test_contains_one_item()
    {
        $this->assertFalse($this->collection->containsOneItem());
    }

    public function test_first_or_fail_success()
    {
        $this->assertMatchesSnapshot($this->collection->firstOrFail('age', '<', 99));
    }

    public function test_first_or_fail_fail()
    {
        $this->expectException(ItemNotFoundException::class);

        $this->collection->firstOrFail('age', '>', 99);
    }

    public function test_sole_unique()
    {
        $this->assertMatchesSnapshot($this->collection->sole('name', '=', 'Alpha'));
    }

    public function test_sole_nonunique()
    {
        $this->assertNull($this->collection->sole('chips', '=', 'true'));
    }
}