<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Helpers;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Exception\Protocol\ProtocolException;
use Illuminate\Testing\TestResponse;

trait UseODataAssertions
{
    protected function assertODataError(Request $request, int $code): TestResponse
    {
        $emptyCodes = [Response::HTTP_NO_CONTENT, Response::HTTP_FOUND, Response::HTTP_NOT_MODIFIED];

        try {
            $response = $this->req($request);
        } catch (ProtocolException $e) {
            $response = new TestResponse($e->toResponse());
            $response->withException($e);
        }

        $content = $this->getResponseContent($response);
        $this->assertEquals($code, $response->getStatusCode(), $content);

        if (!$content) {
            $this->assertContains($code, $emptyCodes);
            return $response;
        }

        if (in_array($code, $emptyCodes)) {
            $this->assertEmpty($content);
        }

        $this->assertMatchesSnapshot($content, new StreamingJsonDriver());

        return $response;
    }

    protected function assertPaginationSequence(
        Request $request,
        ?int $pages = PHP_INT_MAX,
        string $paginationProperty = '@nextLink',
        ?int $expected = null
    ): void {
        $pageCount = 0;
        $entityCount = 0;

        $page = $this->getResponseBody(
            $this->assertJsonResponseSnapshot($request)
        );

        if (property_exists($page, 'value')) {
            $entityCount += count($page->value);
        }

        while (property_exists($page, $paginationProperty) && ++$pageCount < $pages) {
            $page = $this->getResponseBody(
                $this->assertJsonResponseSnapshot(
                    $this->urlToReq($page->$paginationProperty)
                )
            );

            if (property_exists($page, 'value')) {
                $entityCount += count($page->value);
            }
        }

        if ($expected) {
            $this->assertEquals($expected, $entityCount);
        }
    }

    protected function assertNotFound(Request $request): TestResponse
    {
        return $this->assertODataError($request, Response::HTTP_NOT_FOUND);
    }

    protected function assertFound(Request $request): TestResponse
    {
        return $this->assertODataError($request, Response::HTTP_FOUND);
    }

    protected function assertForbidden(Request $request): TestResponse
    {
        return $this->assertODataError($request, Response::HTTP_FORBIDDEN);
    }

    protected function assertUnauthorized(Request $request): TestResponse
    {
        return $this->assertODataError($request, Response::HTTP_UNAUTHORIZED);
    }

    protected function assertAccepted(Request $request): TestResponse
    {
        return $this->assertODataError($request, Response::HTTP_ACCEPTED);
    }

    protected function assertBadRequest(Request $request): TestResponse
    {
        return $this->assertODataError($request, Response::HTTP_BAD_REQUEST);
    }

    protected function assertNotImplemented(Request $request): TestResponse
    {
        return $this->assertODataError($request, Response::HTTP_NOT_IMPLEMENTED);
    }

    protected function assertPreconditionFailed(Request $request): TestResponse
    {
        return $this->assertODataError($request, Response::HTTP_PRECONDITION_FAILED);
    }

    protected function assertNotModified(Request $request): TestResponse
    {
        return $this->assertODataError($request, Response::HTTP_NOT_MODIFIED);
    }

    protected function assertNotAcceptable(Request $request): TestResponse
    {
        return $this->assertODataError($request, Response::HTTP_NOT_ACCEPTABLE);
    }

    protected function assertInternalServerError(Request $request): TestResponse
    {
        return $this->assertODataError($request, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    protected function assertNoContent(Request $request): TestResponse
    {
        return $this->assertODataError($request, Response::HTTP_NO_CONTENT);
    }

    protected function assertMethodNotAllowed(Request $request): TestResponse
    {
        return $this->assertODataError($request, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    protected function assertConflict(Request $request): TestResponse
    {
        return $this->assertODataError($request, Response::HTTP_CONFLICT);
    }

    protected function assertResultCount(TestResponse $response, int $count)
    {
        $this->assertEquals($count, count(json_decode($response->streamedContent())->value));
    }
}
