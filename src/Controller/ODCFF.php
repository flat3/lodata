<?php

namespace Flat3\OData\Controller;

use DOMDocument;
use Flat3\OData\ODataModel;
use Flat3\OData\Exception\Protocol\NotFoundException;
use Flat3\OData\ServiceProvider;
use Flat3\OData\Traits\HasIdentifier;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ODCFF extends Controller
{
    public const content_type = 'text/x-ms-odc; charset=utf-8';

    public function get(ODataModel $model, $identifier)
    {
        $response = new Response();
        $response->header('content-type', self::content_type);

        $htmlDoc = new DOMDocument();

        /** @var HasIdentifier $resource */
        $resource = $model->getResources()->get($identifier);
        if (null === $resource) {
            throw NotFoundException::factory(
                'resource_not_found',
                'The requested resource did not exist'
            )->target($identifier);
        }

        $resourceName = $resource->getTitle() ?: $resource->getIdentifier()->get();
        $resourceId = $resource->getIdentifier()->get();
        $office = 'urn:schemas-microsoft-com:office:office';
        $odc = 'urn:schemas-microsoft-com:office:odc';

        $html = $htmlDoc->createElementNS('http://www.w3.org/TR/REC-html40', 'html');
        $html->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:o', $office);
        $html->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:odc', $odc);

        $head = $htmlDoc->createElement('head');

        $meta = $htmlDoc->createElement('meta');
        $meta->setAttribute('http-equiv', 'Content-Type');
        $meta->setAttribute('content', $this::content_type);
        $head->appendChild($meta);

        $meta = $htmlDoc->createElement('meta');
        $meta->setAttribute('name', 'ProgId');
        $meta->setAttribute('content', 'ODC.Database');
        $head->appendChild($meta);

        $meta = $htmlDoc->createElement('meta');
        $meta->setAttribute('name', 'SourceType');
        $meta->setAttribute('content', 'OLEDB');
        $head->appendChild($meta);

        $title = $htmlDoc->createElement('title');
        $title->textContent = 'Query - '.$resourceName;
        $head->appendChild($title);

        $xml = $htmlDoc->createElement('xml');
        $xml->setAttribute('id', 'docprops');
        $documentProperties = $htmlDoc->createElement('o:DocumentProperties');
        $documentProperties->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns',
            'http://www.w3.org/TR/REC-html40'
        );
        $documentProperties->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:o', $office);

        $description = $htmlDoc->createElement('o:Description');
        $description->textContent = sprintf("Connection to the '%s' query in the workbook.", $resourceName);
        $documentProperties->appendChild($description);

        $name = $htmlDoc->createElement('o:Name');
        $name->textContent = $resourceName;
        $documentProperties->appendChild($name);

        $xml->appendChild($documentProperties);
        $head->appendChild($xml);

        $xml = $htmlDoc->createElement('xml');
        $xml->setAttribute('id', 'msodc');

        $officeDataConnection = $htmlDoc->createElement('odc:OfficeDataConnection');
        $officeDataConnection->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns',
            'http://www.w3.org/TR/REC-html40'
        );
        $officeDataConnection->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:odc', $odc);

        $powerQueryConnection = $htmlDoc->createElement('odc:PowerQueryConnection');
        $powerQueryConnection->setAttribute('odc:Type', 'OLEDB');
        $connectionString = $htmlDoc->createElement('odc:ConnectionString');
        $connectionString->textContent = urldecode(http_build_query([
            'Provider' => 'Microsoft.Mashup.OleDb.1',
            'Data Source' => '$Workbook$',
            'Location' => $resourceId,
        ], null, ';'));
        $powerQueryConnection->appendChild($connectionString);

        $commandType = $htmlDoc->createElement('odc:CommandType');
        $commandType->textContent = 'SQL';
        $powerQueryConnection->appendChild($commandType);

        $commandText = $htmlDoc->createElement('odc:CommandText');
        $commandText->textContent = sprintf('SELECT * FROM [%s]', $resourceId);
        $powerQueryConnection->appendChild($commandText);

        $officeDataConnection->appendChild($powerQueryConnection);

        $powerQueryMashupData = $htmlDoc->createElement('odc:PowerQueryMashupData');

        $mashupDoc = new DOMDocument();

        $mashup = $mashupDoc->createElementNS('http://schemas.microsoft.com/DataMashup', 'Mashup');
        $mashup->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:xsi',
            'http://www.w3.org/2001/XMLSchema-instance'
        );
        $mashup->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsd', 'http://www.w3.org/2001/XMLSchema');

        $client = $mashupDoc->createElement('Client');
        $client->textContent = 'EXCEL';
        $mashup->appendChild($client);

        $version = $mashupDoc->createElement('Version');
        $version->textContent = '2.83.5894.761';
        $mashup->appendChild($version);

        $minVersion = $mashupDoc->createElement('MinVersion');
        $minVersion->textContent = '2.21.0.0';
        $mashup->appendChild($minVersion);

        $culture = $mashupDoc->createElement('Culture');
        $culture->textContent = 'en-US';
        $mashup->appendChild($culture);

        $safeCombine = $mashupDoc->createElement('SafeCombine');
        $safeCombine->textContent = 'true';
        $mashup->appendChild($safeCombine);

        $items = $mashupDoc->createElement('Items');

        $query = $mashupDoc->createElement('Query');
        $query->setAttribute('Name', $resourceId);

        $formula = $mashupDoc->createElement('Formula');
        $formulaContent = $mashupDoc->createCDATASection(sprintf(
            'let Source = OData.Feed("%1$s", null, [Implementation="2.0"]), %2$s_table = Source{[Name="%2$s",Signature="table"]}[Data] in %2$s_table',
            ServiceProvider::restEndpoint(),
            $resourceId
        ));
        $formula->appendChild($formulaContent);

        $query->appendChild($formula);

        $isParameterQuery = $mashupDoc->createElement('IsParameterQuery');
        $isParameterQuery->setAttribute('xsi:nil', 'true');
        $query->appendChild($isParameterQuery);

        $isDirectQuery = $mashupDoc->createElement('IsDirectQuery');
        $isDirectQuery->setAttribute('xsi:nil', 'true');
        $query->appendChild($isDirectQuery);

        $items->appendChild($query);
        $mashup->appendChild($items);
        $mashupDoc->appendChild($mashup);
        $mashupText = $mashupDoc->saveHTML();

        $powerQueryMashupData->textContent = $mashupText;

        $officeDataConnection->appendChild($powerQueryMashupData);
        $xml->appendChild($officeDataConnection);

        $head->appendChild($xml);
        $html->appendChild($head);
        $htmlDoc->appendChild($html);

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $resource.'.odc'
        );
        $response->headers->set('Content-Disposition', $disposition);
        $response->setContent($htmlDoc->saveHTML());
        return $response;
    }
}
