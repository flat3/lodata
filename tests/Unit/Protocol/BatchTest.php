<?php

namespace Flat3\Lodata\Tests\Unit\Protocol;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class BatchTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->withFlightModel();
    }

    public function test_bad_content_type()
    {
        $this->assertNotAcceptable(
            Request::factory()
                ->path('/$batch')
                ->xml()
                ->post()
                ->body('')
        );
    }

    public function test_full_url()
    {
        $this->assertTextMetadataResponse(
            Request::factory()
                ->path('/$batch')
                ->header('content-type', 'multipart/mixed; boundary=batch_36522ad7-fc75-4b56-8c71-56071383e77b')
                ->post()
                ->multipart(<<<MULTIPART
--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: application/http

GET http://localhost/odata/flights(1)
Host: localhost


--batch_36522ad7-fc75-4b56-8c71-56071383e77b
MULTIPART
                )
        );
    }

    public function test_absolute_path()
    {
        $this->assertTextMetadataResponse(
            Request::factory()
                ->path('/$batch')
                ->header('content-type', 'multipart/mixed; boundary=batch_36522ad7-fc75-4b56-8c71-56071383e77b')
                ->post()
                ->multipart(<<<MULTIPART
--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: application/http

GET /odata/flights(1)
Host: localhost


--batch_36522ad7-fc75-4b56-8c71-56071383e77b
MULTIPART
                )
        );
    }

    public function test_relative_path()
    {
        $this->assertTextMetadataResponse(
            Request::factory()
                ->path('/$batch')
                ->header('content-type', 'multipart/mixed; boundary=batch_36522ad7-fc75-4b56-8c71-56071383e77b')
                ->post()
                ->multipart(<<<MULTIPART
--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: application/http

GET flights(1)
Host: localhost


--batch_36522ad7-fc75-4b56-8c71-56071383e77b
MULTIPART
                )
        );
    }

    public function test_not_found()
    {
        $this->assertTextMetadataResponse(
            Request::factory()
                ->path('/$batch')
                ->header('content-type', 'multipart/mixed; boundary=batch_36522ad7-fc75-4b56-8c71-56071383e77b')
                ->post()
                ->multipart(<<<MULTIPART
--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: application/http

GET notfound
Host: localhost


--batch_36522ad7-fc75-4b56-8c71-56071383e77b
MULTIPART
                )
        );
    }

    public function test_batch()
    {
        $this->assertTextMetadataResponse(
            Request::factory()
                ->path('/$batch')
                ->header('content-type', 'multipart/mixed; boundary=batch_36522ad7-fc75-4b56-8c71-56071383e77b')
                ->post()
                ->multipart(<<<MULTIPART
--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: application/http

GET /odata/flights(1)
Host: localhost


--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: multipart/mixed; boundary=changeset_77162fcd-b8da-41ac-a9f8-9357efbbd

--changeset_77162fcd-b8da-41ac-a9f8-9357efbbd
Content-Type: application/http
Content-ID: 1

POST /odata/airports HTTP/1.1
Host: localhost
Content-Type: application/json

{
  "name": "One",
  "code": "one"
}
--changeset_77162fcd-b8da-41ac-a9f8-9357efbbd
Content-Type: application/http
Content-ID: 2

PATCH /odata/airports(1) HTTP/1.1
Host: localhost
Content-Type: application/json
If-Match: xxxxx
Prefer: return=minimal

{
  "code": "xyz"
}
--changeset_77162fcd-b8da-41ac-a9f8-9357efbbd--
--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: application/http

GET /odata/airports HTTP/1.1
Host: localhost


--batch_36522ad7-fc75-4b56-8c71-56071383e77b--

MULTIPART
                )
        );
    }
}

