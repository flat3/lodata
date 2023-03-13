<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Protocol;

use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Transaction\MediaType;

class MediaTypeTest extends TestCase
{
    static public function typeProvider()
    {
        return [
            [
                'application/json',
                'application/json',
                'application',
                'json',
            ],
            [
                'text/plain; charset=UTF-8',
                'text/plain;charset=UTF-8',
                'text',
                'plain',
                ['charset' => 'UTF-8'],
            ],
            [
                'multipart/mixed;param=true',
                'multipart/mixed;param=true',
                'multipart',
                'mixed',
                ['param' => 'true'],
            ],
            [
                'multipart/mixed; param=true',
                'multipart/mixed;param=true',
                'multipart',
                'mixed',
                ['param' => 'true'],
            ],
            [
                'multipart/mixed; param=true; this=false',
                'multipart/mixed;param=true;this=false',
                'multipart',
                'mixed',
                ['param' => 'true', 'this' => 'false'],
            ],
        ];
    }

    /**
     * @dataProvider typeProvider
     */
    public function test_types(
        string $original,
        string $normalised,
        string $type,
        string $subtype,
        array $parameters = []
    ) {
        $mt = (new MediaType)->parse($original);
        $this->assertEquals($type, $mt->getType());
        $this->assertEquals($subtype, $mt->getSubtype());

        foreach ($parameters as $key => $value) {
            $this->assertEquals($value, $mt->getParameter($key));
        }

        $this->assertEquals($normalised, (string) $mt);
    }
}
