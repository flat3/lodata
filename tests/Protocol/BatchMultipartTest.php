<?php

namespace Flat3\Lodata\Tests\Protocol;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Tests\Drivers\WithSQLDriver;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type\Int32;

/**
 * @group sql
 */
class BatchMultipartTest extends TestCase
{
    use WithSQLDriver;

    public function test_bad_content_type()
    {
        $this->assertNotAcceptable(
            (new Request)
                ->path('/$batch')
                ->xml()
                ->post()
                ->body('')
        );
    }

    public function test_invalid_multipart_missing_boundary()
    {
        $this->assertBadRequest(
            (new Request)
                ->path('/$batch')
                ->header('content-type', 'multipart/mixed')
                ->post()
                ->multipart('')
        );
    }

    public function test_multipart_no_epilogue()
    {
        $this->assertTextMetadataResponse(
            (new Request)
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

    public function test_multipart_ignores_prologue()
    {
        $this->assertTextMetadataResponse(
            (new Request)
                ->path('/$batch')
                ->header('content-type', 'multipart/mixed; boundary=batch_36522ad7-fc75-4b56-8c71-56071383e77b')
                ->post()
                ->multipart(<<<MULTIPART
hello!
--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: application/http

GET http://localhost/odata/flights(1)
Host: localhost


--batch_36522ad7-fc75-4b56-8c71-56071383e77b--
MULTIPART
                )
        );
    }

    public function test_full_url()
    {
        $this->assertTextMetadataResponse(
            (new Request)
                ->path('/$batch')
                ->header('content-type', 'multipart/mixed; boundary=batch_36522ad7-fc75-4b56-8c71-56071383e77b')
                ->post()
                ->multipart(<<<MULTIPART
--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: application/http

GET http://localhost/odata/flights(1)
Host: localhost


--batch_36522ad7-fc75-4b56-8c71-56071383e77b--
MULTIPART
                )
        );
    }

    public function test_query_param()
    {
        $this->assertTextMetadataResponse(
            (new Request)
                ->path('/$batch')
                ->header('content-type', 'multipart/mixed; boundary=batch_36522ad7-fc75-4b56-8c71-56071383e77c')
                ->post()
                ->multipart(<<<MULTIPART
--batch_36522ad7-fc75-4b56-8c71-56071383e77c
Content-Type: application/http

GET http://localhost/odata/flights?\$top=1
Host: localhost


--batch_36522ad7-fc75-4b56-8c71-56071383e77c--
MULTIPART
                )
        );
    }

    public function test_absolute_path()
    {
        $this->assertTextMetadataResponse(
            (new Request)
                ->path('/$batch')
                ->header('content-type', 'multipart/mixed; boundary=batch_36522ad7-fc75-4b56-8c71-56071383e77b')
                ->post()
                ->multipart(<<<MULTIPART
--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: application/http

GET /odata/flights(1)
Host: localhost


--batch_36522ad7-fc75-4b56-8c71-56071383e77b--
MULTIPART
                )
        );
    }

    public function test_service_document()
    {
        $this->assertTextMetadataResponse(
            (new Request)
                ->path('/$batch')
                ->header('content-type', 'multipart/mixed; boundary=batch_36522ad7-fc75-4b56-8c71-56071383e77b')
                ->post()
                ->multipart(<<<MULTIPART
--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: application/http

GET /odata/
Host: localhost


--batch_36522ad7-fc75-4b56-8c71-56071383e77b--
MULTIPART
                )
        );
    }

    public function test_metadata_document()
    {
        $this->assertTextMetadataResponse(
            (new Request)
                ->path('/$batch')
                ->header('content-type', 'multipart/mixed; boundary=batch_36522ad7-fc75-4b56-8c71-56071383e77b')
                ->post()
                ->multipart(<<<'MULTIPART'
--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: application/http

GET /odata/$metadata
Host: localhost


--batch_36522ad7-fc75-4b56-8c71-56071383e77b--
MULTIPART
                )
        );
    }

    public function test_action_invocation()
    {
        $aa1 = new Operation\Action('aa1');
        $aa1->setCallable(function (Int32 $a, Int32 $b): Int32 {
            return new Int32($a->get() + $b->get());
        });
        Lodata::add($aa1);

        $this->assertTextMetadataResponse(
            (new Request)
                ->path('/$batch')
                ->header('content-type', 'multipart/mixed; boundary=batch_36522ad7-fc75-4b56-8c71-56071383e77b')
                ->post()
                ->multipart(<<<'MULTIPART'
--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: application/http

POST /odata/aa1
Host: localhost
Content-Type: application/json

{
  "a": 3,
  "b": 4
}

--batch_36522ad7-fc75-4b56-8c71-56071383e77b--
MULTIPART
                )
        );
    }

    public function test_function_invocation()
    {
        $aa1 = new Operation\Function_('aa1');
        $aa1->setCallable(function (Int32 $a, Int32 $b): Int32 {
            return new Int32($a->get() + $b->get());
        });
        Lodata::add($aa1);

        $this->assertTextMetadataResponse(
            (new Request)
                ->path('/$batch')
                ->header('content-type', 'multipart/mixed; boundary=batch_36522ad7-fc75-4b56-8c71-56071383e77b')
                ->post()
                ->multipart(<<<'MULTIPART'
--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: application/http

GET /odata/aa1(a=3,b=4)
Host: localhost

--batch_36522ad7-fc75-4b56-8c71-56071383e77b--
MULTIPART
                )
        );
    }

    public function test_relative_path()
    {
        $this->assertTextMetadataResponse(
            (new Request)
                ->path('/$batch')
                ->header('content-type', 'multipart/mixed; boundary=batch_36522ad7-fc75-4b56-8c71-56071383e77b')
                ->post()
                ->multipart(<<<MULTIPART
--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: application/http

GET flights(1)
Host: localhost


--batch_36522ad7-fc75-4b56-8c71-56071383e77b--
MULTIPART
                )
        );
    }

    public function test_non_crlf_newlines()
    {
        $this->assertTextMetadataResponse(
            (new Request)
                ->path('/$batch')
                ->header('content-type', 'multipart/mixed; boundary=batch_36522ad7-fc75-4b56-8c71-56071383e77b')
                ->post()
                ->multipart(<<<MULTIPART
--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: application/http

GET flights(1)
Host: localhost


--batch_36522ad7-fc75-4b56-8c71-56071383e77b--
MULTIPART, false
                )
        );
    }

    public function test_prefer_metadata()
    {
        $this->assertTextMetadataResponse(
            (new Request)
                ->path('/$batch')
                ->header('content-type', 'multipart/mixed; boundary=batch_36522ad7-fc75-4b56-8c71-56071383e77b')
                ->post()
                ->multipart(<<<MULTIPART
--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: application/http

GET flights(1)
Host: localhost
Accept: application/json;odata.metadata=full


--batch_36522ad7-fc75-4b56-8c71-56071383e77b--
MULTIPART
                )
        );
    }

    public function test_no_accept_header()
    {
        $this->assertTextMetadataResponse(
            (new Request)
                ->path('/$batch')
                ->header('content-type', 'multipart/mixed; boundary=batch_36522ad7-fc75-4b56-8c71-56071383e77b')
                ->unsetHeader('accept')
                ->post()
                ->multipart(<<<MULTIPART
--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: application/http

GET flights(1)
Host: localhost


--batch_36522ad7-fc75-4b56-8c71-56071383e77b--
MULTIPART
                )
        );
    }

    public function test_not_found()
    {
        $this->assertTextMetadataResponse(
            (new Request)
                ->path('/$batch')
                ->header('content-type', 'multipart/mixed; boundary=batch_36522ad7-fc75-4b56-8c71-56071383e77b')
                ->post()
                ->multipart(<<<MULTIPART
--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: application/http

GET notfound
Host: localhost


--batch_36522ad7-fc75-4b56-8c71-56071383e77b--
MULTIPART
                )
        );
    }

    public function test_bad_request()
    {
        $this->assertTextMetadataResponse(
            (new Request)
                ->path('/$batch')
                ->header('content-type', 'multipart/mixed; boundary=batch_36522ad7-fc75-4b56-8c71-56071383e77b')
                ->post()
                ->multipart(<<<MULTIPART
--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: application/http

GET flights('a')
Host: localhost


--batch_36522ad7-fc75-4b56-8c71-56071383e77b--
MULTIPART
                )
        );
    }

    public function test_batch()
    {
        $this->assertTextMetadataResponse(
            (new Request)
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
Prefer: return=minimal
If-Match: W/"73fa0e567cdc8392d1869d47b3f0886db629d38780a5f2010ce767900cde7266"

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

    public function test_bad_document_content_type()
    {
        $this->assertTextMetadataResponse(
            (new Request)
                ->path('/$batch')
                ->header('content-type', 'multipart/mixed; boundary=batch_36522ad7-fc75-4b56-8c71-56071383e77b')
                ->post()
                ->multipart(<<<MULTIPART
--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: application/http
Content-ID: 1

POST /odata/airports HTTP/1.1
Host: localhost

{
  "name": "One",
  "code": "one"
}

--batch_36522ad7-fc75-4b56-8c71-56071383e77b--

MULTIPART
                )
        );
    }


    public function test_ifmatch_failed()
    {
        $this->assertTextMetadataResponse(
            (new Request)
                ->path('/$batch')
                ->header('content-type', 'multipart/mixed; boundary=batch_36522ad7-fc75-4b56-8c71-56071383e77b')
                ->post()
                ->multipart(<<<'MULTIPART'
--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: application/http

POST airports HTTP/1.1
Host: localhost
Content-Type: application/json
Content-ID: 1

{
  "name": "Test1",
  "code": "xyz" 
}

--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: application/http

PATCH $1 HTTP/1.1
Host: localhost
Content-Type: application/json
If-Match: xxx

{
  "name": "Test2",
  "code": "abc"
}
--batch_36522ad7-fc75-4b56-8c71-56071383e77b--
MULTIPART
                )
        );
    }

    public function test_reference_returned_entity()
    {
        $this->assertTextMetadataResponse(
            (new Request)
                ->path('/$batch')
                ->header('content-type', 'multipart/mixed; boundary=batch_36522ad7-fc75-4b56-8c71-56071383e77b')
                ->post()
                ->multipart(<<<'MULTIPART'
--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: application/http

POST airports HTTP/1.1
Host: localhost
Content-Type: application/json
Content-ID: 1

{
  "name": "Test1",
  "code": "xyz" 
}

--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: application/http

PATCH $1 HTTP/1.1
Host: localhost
Content-Type: application/json

{
  "name": "Test2",
  "code": "abc"
}
--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: application/http

GET $1 HTTP/1.1
Host: localhost

--batch_36522ad7-fc75-4b56-8c71-56071383e77b--
MULTIPART
                )
        );
    }
}

