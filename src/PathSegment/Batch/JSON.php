<?php

declare(strict_types=1);

namespace Flat3\Lodata\PathSegment\Batch;

use Flat3\Lodata\Controller\Request;
use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\NotImplementedException;
use Flat3\Lodata\Exception\Protocol\ProtocolException;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Helper\Url;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\JsonInterface;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Flat3\Lodata\Interfaces\ResponseInterface;
use Flat3\Lodata\PathSegment\Batch;
use Flat3\Lodata\ServiceProvider;
use Flat3\Lodata\Transaction\MediaType;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * JSON
 * @package Flat3\Lodata\PathSegment\Batch
 */
class JSON extends Batch implements JsonInterface, ResponseInterface
{
    /**
     * Requests in this batch
     * @var array $requests
     */
    protected $requests = [];

    public function emitJson(Transaction $transaction): void
    {
        $transaction->outputJsonObjectStart();
        $transaction->outputJsonKey('responses');
        $transaction->outputJsonArrayStart();

        $emitSeparator = false;

        while ($this->requests) {
            if ($emitSeparator) {
                $transaction->outputJsonSeparator();
            }

            $transaction->outputJsonObjectStart();
            $requestData = array_shift($this->requests);

            $requestTransaction = new Transaction();

            $requestURI = $requestData['url'];

            switch (true) {
                case Str::startsWith($requestURI, '/'):
                    $uri = Url::http_build_url(
                        ServiceProvider::endpoint(),
                        $requestURI,
                        Url::HTTP_URL_REPLACE
                    );
                    break;

                case Str::startsWith($requestURI, '$'):
                    $uri = $requestURI;
                    break;

                default:
                    $uri = Url::http_build_url(
                        ServiceProvider::endpoint(),
                        $requestURI,
                        Url::HTTP_URL_JOIN_PATH | Url::HTTP_URL_JOIN_QUERY
                    );
                    break;
            }

            $body = $requestData['body'] ?? null;

            $request = \Illuminate\Http\Request::create(
                $uri,
                $requestData['method'],
                [],
                [],
                [],
                [],
                \Flat3\Lodata\Helper\JSON::encode($body)
            );

            $headers = array_change_key_case($requestData['headers'] ?? [], CASE_LOWER);

            if (!array_key_exists(Constants::contentType, $headers)) {
                $headers[Constants::contentType] = MediaType::json;
            }

            $request->headers->replace($headers);

            $requestTransaction->initialize(new Request($request));

            $response = null;

            try {
                $this->maybeSwapContentUrl($requestTransaction);
                $response = $requestTransaction->execute();
            } catch (ProtocolException $e) {
                $response = $e->toResponse();
            }

            $transaction->outputJsonKV([
                'id' => $requestData['id'],
                'status' => $response->getStatusCode(),
            ]);

            $responseHeaders = [];

            foreach ($this->getResponseHeaders($response) as $key => $values) {
                $responseHeaders[$key] = $values[0];
            }

            if ($responseHeaders) {
                $transaction->outputJsonSeparator();
                $transaction->outputJsonKey('headers');
                $transaction->outputJsonObjectStart();
                $transaction->outputJsonKV($responseHeaders);
                $transaction->outputJsonObjectEnd();
            }

            if ($response->getStatusCode() !== Response::HTTP_NO_CONTENT) {
                $transaction->outputJsonSeparator();
                $transaction->outputJsonKey('body');

                switch ($response->getContentType()->getSubtype()) {
                    case 'json':
                        $response->sendContent();
                        break;

                    default:
                        ob_start();
                        $response->sendContent();
                        $transaction->sendJson(ob_get_clean());
                        break;
                }
            }

            if ($response->getResource() instanceof ResourceInterface) {
                $this->setReference(
                    (string) $requestData['id'],
                    $response->getResource()->getResourceUrl($requestTransaction)
                );
            }

            $transaction->outputJsonObjectEnd();

            $emitSeparator = true;
        }

        $transaction->outputJsonArrayEnd();
        $transaction->outputJsonObjectEnd();
    }

    public function response(Transaction $transaction, ?ContextInterface $context = null): Response
    {
        $transaction->assertContentTypeJson();

        $transaction->sendContentType(
            (new MediaType)
                ->parse(MediaType::json)
        );

        $body = $transaction->getBody();

        if (!array_key_exists('requests', $body) || !is_array($body['requests'])) {
            throw new BadRequestException(
                'missing_requests',
                'The provided JSON document did not contain a valid requests property'
            );
        }

        $requests = $body['requests'];

        foreach ($requests as $request) {
            if (!Arr::has($request, ['id', 'method', 'url'])) {
                throw new BadRequestException(
                    'missing_request_properties',
                    'All requests must contain the "id", "method" and "url" properties'
                );
            }

            if (array_key_exists('atomicityGroup', $request)) {
                throw new NotImplementedException('atomicity_not_available', 'Atomicity groups are not supported');
            }

            if (!in_array(strtolower($request['method'] ?? ''), ['delete', 'get', 'patch', 'post', 'put'])) {
                throw new BadRequestException(
                    'incorrect_request_method',
                    sprintf('Request %s had an invalid method "%s"', $request['id'], $request['method'])
                );
            }
        }

        $this->requests = $requests;

        return $transaction->getResponse()->setCallback(function () use ($transaction) {
            $this->emitJson($transaction);
        });
    }
}