<?php

namespace Flat3\Lodata\PathSegment;

use Flat3\Lodata\Annotation\Core\V1\Description;
use Flat3\Lodata\Annotation\Core\V1\LongDescription;
use Flat3\Lodata\Annotation\Core\V1\SchemaVersion;
use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\EntitySet\QueryInterface;
use Flat3\Lodata\Interfaces\JsonInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\ResponseInterface;
use Flat3\Lodata\Singleton;
use Flat3\Lodata\Transaction\MediaType;
use Illuminate\Http\Request;
use stdClass;

class OpenAPI implements PipeInterface, ResponseInterface, JsonInterface
{
    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): ?PipeInterface {
        if ($currentSegment !== 'openapi.json') {
            throw new PathNotHandledException();
        }

        if ($argument || $nextSegment) {
            throw new BadRequestException('openapi_argument', 'openapi.json must be the only argument in the path');
        }

        $transaction->assertMethod(Request::METHOD_GET);

        return new self();
    }

    public function response(Transaction $transaction, ?ContextInterface $context = null): Response
    {
        $transaction->sendContentType(MediaType::factory()->parse(MediaType::json));

        return $transaction->getResponse()->setCallback(function () use ($transaction) {
            $this->emitJson($transaction);
        });
    }

    public function emitJson(Transaction $transaction): void
    {
        $oas = new stdClass();
        $oas->openapi = '3.0.1';

        $info = new stdClass();
        $oas->info = $info;

        $description = Description::getModelAnnotation();
        $info->title = $description ? $description->toJson() : 'OData Service for namespace '.Lodata::getNamespace();

        $schemaVersion = SchemaVersion::getModelAnnotation();
        $info->version = $schemaVersion ? $schemaVersion->toJson() : '1.0.0';

        $longDescription = LongDescription::getModelAnnotation();
        $info->description = $longDescription ? $longDescription->toJson() : sprintf('This OData service is located at [%1$s](%1$s)\n\n## References\n- [Org.OData.Core.V1](https://github.com/oasis-tcs/odata-vocabularies/blob/master/vocabularies/Org.OData.Core.V1.md)\n- [Org.OData.Measures.V1](https://github.com/oasis-tcs/odata-vocabularies/blob/master/vocabularies/Org.OData.Measures.V1.md)',
            Lodata::getEndpoint());

        $oas->servers = [
            [
                'url' => '.',
            ]
        ];

        $tags = [];

        /** @var EntitySet|Singleton $resource */
        foreach (Lodata::getResources()->sliceByClass([Singleton::class, EntitySet::class]) as $resource) {
            $tag = [
                'name' => $resource->getName(),
            ];

            $description = $resource->getAnnotations()->sliceByClass(Description::class)->first();
            if ($description) {
                $tag['description'] = $description->toJson();
            }

            $tags[] = $tag;
        }

        // The tags array can contain additional Tag Objects for other logical groups, e.g. for action imports or function imports that are not associated with an entity set, or for entity types that are only used in containment navigation properties or action/function return types.
        $oas->tags = $tags;

        $paths = new stdClass();
        $oas->paths = $paths;

        /** @var EntitySet $entitySet */
        foreach (Lodata::getResources()->sliceByClass(EntitySet::class) as $entitySet) {
            $pathItemObject = new stdClass();

            if ($entitySet instanceof QueryInterface) {
                $queryObject = new stdClass();
                $pathItemObject->{'get'} = $queryObject;
            }

            $paths->{'/'.$entitySet->getName()} = $pathItemObject;
        }

        $transaction->sendJson($oas);
    }
}