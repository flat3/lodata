<?php

declare(strict_types=1);

namespace Flat3\Lodata\Controller;

use DOMDocument;
use Flat3\Lodata\Exception\Protocol\NotFoundException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\ServiceProvider;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * ODCFF
 * @link https://docs.microsoft.com/en-us/openspecs/office_file_formats/ms-odcff/09a237b3-a761-4847-a54c-eb665f5b0a6e
 * @package Flat3\Lodata\Controller
 */
class ODCFF extends Controller
{
    public const contentType = 'text/x-ms-odc; charset=utf-8';

    /**
     * Generate an ODCFF response for the provided entity set identifier
     * @param  string  $identifier  Identifier
     * @return Response Client response
     */
    public function get(string $identifier): Response
    {
        $response = App::make(Response::class);
        $response->header(Constants::contentType, self::contentType);

        $htmlDoc = new DOMDocument();

        $entitySet = Lodata::getEntitySet($identifier);
        if (null === $entitySet) {
            throw (new NotFoundException(
                'resource_not_found',
                'The requested resource did not exist'
            ))->target($identifier);
        }

        $resourceName = $entitySet->getTitle() ?: $entitySet->getName();
        $resourceId = $entitySet->getName();
        $office = 'urn:schemas-microsoft-com:office:office';
        $odc = 'urn:schemas-microsoft-com:office:odc';

        $html = $htmlDoc->createElementNS('http://www.w3.org/TR/REC-html40', 'html');
        $html->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:o', $office);
        $html->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:odc', $odc);

        $head = $htmlDoc->createElement('head');

        $meta = $htmlDoc->createElement('meta');
        $meta->setAttribute('http-equiv', 'Content-Type');
        $meta->setAttribute('content', $this::contentType);
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

        $nameElement = $htmlDoc->createElement('o:Name');
        $nameElement->textContent = $resourceName;
        $documentProperties->appendChild($nameElement);

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
        ], '', ';'));
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
        $safeCombine->textContent = Constants::true;
        $mashup->appendChild($safeCombine);

        $items = $mashupDoc->createElement('Items');

        $query = $mashupDoc->createElement('Query');
        $query->setAttribute('Name', $resourceId);

        $formula = $mashupDoc->createElement('Formula');
        $formulaContent = $mashupDoc->createCDATASection(sprintf(
            'let Source = OData.Feed("%1$s", null, [Implementation="2.0"]), %2$s_table = Source{[Name="%2$s",Signature="table"]}[Data] in %2$s_table',
            ServiceProvider::endpoint(),
            $resourceId,
        ));
        $formula->appendChild($formulaContent);

        $query->appendChild($formula);

        $isParameterQuery = $mashupDoc->createElement('IsParameterQuery');
        $isParameterQuery->setAttribute('xsi:nil', Constants::true);
        $query->appendChild($isParameterQuery);

        $isDirectQuery = $mashupDoc->createElement('IsDirectQuery');
        $isDirectQuery->setAttribute('xsi:nil', Constants::true);
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
            $entitySet->getIdentifier().'.odc'
        );
        $response->headers->set('Content-Disposition', $disposition);
        $response->setContent($htmlDoc->saveHTML());

        return $response;
    }
}
