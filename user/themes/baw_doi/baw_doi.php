<?php
namespace Grav\Theme;

use Grav\Common\Theme;
use Grav\Plugin\ElasticsearchHelper;
use Grav\Plugin\IdfHelper;
use RocketTheme\Toolbox\Event\Event;

class BawDoi extends Theme
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onThemeDetailMetadataEvent' => ['addThemeMetadataContent', 0],
            'onThemeSearchHitMetadataEvent' => ['addThemeSearchHitMetadataContent', 0],
        ];
    }

    public function addThemeSearchHitMetadataContent(Event $event): void
    {
        // Get variables from event
        $content = $event['content'];
        $hit = $event['hit'];
        $lang = $event['lang'];

        $hit->bwastr_name = ElasticsearchHelper::getValueArray($content, "bwstr-bwastr_name");
        $hit->bwastrs = self::getBwaStrs($content);
        $hit->bawauftragsnummer = ElasticsearchHelper::getValue($content, "bawauftragsnummer");
        $hit->bawauftragstitel = ElasticsearchHelper::getValue($content, "bawauftragstitel");
        $hit->data_category = ElasticsearchHelper::getValue($content, "data_category");
        $hit->citation = ElasticsearchHelper::getValue($content, "additional_html_citation_quote");

    }

    public function addThemeMetadataContent(Event $event): void
    {
        // Get variables from event
        $content = $event['content'];
        $hit = $event['hit'];
        $lang = $event['lang'];

        $node = IdfHelper::getNode($content, '//gmd:MD_Metadata | //idf:idfMdMetadata');

        $hit->doi = self::getDoi($node);
        $hit->citations = self::getCitations($node);
        $hit->bibliographies = self::getBibliographies($node);
        $xpathExpression = "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:report/gmd:DQ_AccuracyOfATimeMeasurement/gmd:result/gmd:DQ_QuantitativeResult/gmd:value/gco:Record";
        $hit->timeMeasureValue = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:report/gmd:DQ_AccuracyOfATimeMeasurement/gmd:result/gmd:DQ_QuantitativeResult/gmd:valueUnit/gml:UnitDefinition/gml:catalogSymbol";
        $hit->timeMeasureUnit = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = './gmd:identificationInfo/*/software/Erstellungsvertrag/vertragsNummer';
        $hit->erstellungsvertragsnummer = array(
            'value' => IdfHelper::getNodeValue($node, $xpathExpression),
            'type' => 'text'
        );
        $xpathExpression = './gmd:identificationInfo/*/software/Erstellungsvertrag/datum';
        $hit->erstellungsvertragsdatum = array(
            'value' => IdfHelper::getNodeValue($node, $xpathExpression),
            'type' => 'date'
        );

        $hit->hierachyLevelName = IdfHelper::getNodeValue($node, "./gmd:hierarchyLevelName/*[self::gco:CharacterString or self::gmx:Anchor]");

        $hit->areaHeight = self::getAreaHeight($node, $lang);
    }

    private static function getAreaHeight(\SimpleXMLElement $node, string $lang): array
    {
        $array = [];
        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:identificationInfo/*/*/gmd:EX_Extent/gmd:verticalElement/gmd:EX_VerticalExtent[./*]");
        foreach ($tmpNodes as $tmpNode) {
            $item = [];

            $item[] = array(
                "value" => IdfHelper::getNodeValue($tmpNode, "./gmd:minimumValue/gco:Real"),
                "type" => "text"
            );

            $item[] = array(
                "value" => IdfHelper::getNodeValue($tmpNode, "./gmd:maximumValue/gco:Real"),
                "type" => "text"
            );

            $item[] = array(
                "value" => IdfHelper::getNodeValue($tmpNode, "./gmd:verticalCRS/@xlink:title"),
                "type" => "text"
            );

            $array[] = $item;
        }
        return $array;
    }

    private static function getCitations(\SimpleXMLElement $node): ?array
    {
        $xpathExpression = './gmd:identificationInfo/*/gmd:pointOfContact/idf:idfResponsibleParty[./gmd:role/gmd:CI_RoleCode/@codeListValue="author"]';
        $tmpNodes = IdfHelper::getNodeList($node, $xpathExpression);
        if (!empty($tmpNodes)) {
            $array = [];
            $array[] = array(
                "author_person" => IdfHelper::getNodeValueList($node, "./gmd:identificationInfo/*/gmd:pointOfContact/idf:idfResponsibleParty[./gmd:role/gmd:CI_RoleCode/@codeListValue='author']/gmd:individualName/*[self::gco:CharacterString or self::gmx:Anchor]"),
                "author_org" => IdfHelper::getNodeValue($node, "./gmd:identificationInfo/*/gmd:pointOfContact/idf:idfResponsibleParty[./gmd:role/gmd:CI_RoleCode/@codeListValue='author']/gmd:organisationName/*[self::gco:CharacterString or self::gmx:Anchor]"),
                "year" => IdfHelper::getNodeValue($node, "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:date/gmd:CI_Date[./gmd:dateType/gmd:CI_DateTypeCode/@codeListValue='publication']/gmd:date/gco:Date|./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:date/gmd:CI_Date[./gmd:dateType/gmd:CI_DateTypeCode/@codeListValue='publication']/gmd:date/gco:DateTime"),
                "title" => IdfHelper::getNodeValue($node, "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:title/*[self::gco:CharacterString or self::gmx:Anchor]"),
                "publisher" => IdfHelper::getNodeValue($node, "./gmd:identificationInfo/*/gmd:pointOfContact/idf:idfResponsibleParty[./gmd:role/gmd:CI_RoleCode/@codeListValue='publisher'][1]/gmd:organisationName/*[self::gco:CharacterString or self::gmx:Anchor]"),
                "doi" => IdfHelper::getNodeValue($node, "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:identifier/gmd:MD_Identifier/gmd:code/*[self::gco:CharacterString or self::gmx:Anchor][contains(text(),'doi')]"),
                "doi_type" => IdfHelper::getNodeValue($node, "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:identifier/gmd:MD_Identifier[contains(./gmd:code/*[self::gco:CharacterString or self::gmx:Anchor]/text(),'doi')]/gmd:authority/gmd:CI_Citation/gmd:identifier/gmd:MD_Identifier/gmd:code/*[self::gco:CharacterString or self::gmx:Anchor]")
            );
            return $array;
        }
        return null;
    }

    private static function getBibliographies(\SimpleXMLElement $node): ?array
    {
        $xpathExpression = './gmd:identificationInfo/*/gmd:aggregationInfo/gmd:MD_AggregateInformation[./gmd:associationType/gmd:DS_AssociationTypeCode/@codeListValue="crossReference"]';
        $tmpNodes = IdfHelper::getNodeList($node, $xpathExpression);
        if (!empty($tmpNodes)) {
            $array = [];
            foreach ($tmpNodes as $tmpNode) {
                $array[] = array(
                    "author_person" => IdfHelper::getNodeValueList($tmpNode, "./gmd:aggregateDataSetName/gmd:citedResponsibleParty/gmd:CI_ResponsibleParty[./gmd:role/gmd:CI_RoleCode/@codeListValue='author']/gmd:individualName/*[self::gco:CharacterString or self::gmx:Anchor]"),
                    "author_org" => IdfHelper::getNodeValue($tmpNode, "./gmd:aggregateDataSetName/gmd:CI_Citation/gmd:citedResponsibleParty/gmd:CI_ResponsibleParty[./gmd:role/gmd:CI_RoleCode/@codeListValue='author']/gmd:organisationName/*[self::gco:CharacterString or self::gmx:Anchor]"),
                    "year" => IdfHelper::getNodeValue($tmpNode, "./gmd:aggregateDataSetName/gmd:CI_Citation/gmd:date/gmd:CI_Date[./gmd:dateType/gmd:CI_DateTypeCode/@codeListValue='publication']/gmd:date/gco:Date"),
                    "title" => IdfHelper::getNodeValue($tmpNode, "./gmd:aggregateDataSetName/gmd:CI_Citation/gmd:title/*[self::gco:CharacterString or self::gmx:Anchor]"),
                    "publisher" => IdfHelper::getNodeValue($tmpNode, "./gmd:aggregateDataSetName/gmd:CI_Citation/gmd:citedResponsibleParty/gmd:CI_ResponsibleParty[./gmd:role/gmd:CI_RoleCode/@codeListValue='publisher'][1]/gmd:organisationName/*[self::gco:CharacterString or self::gmx:Anchor]"),
                    "doi" => IdfHelper::getNodeValue($tmpNode, "./gmd:aggregateDataSetName/gmd:CI_Citation/gmd:identifier/gmd:MD_Identifier/gmd:code/*[self::gco:CharacterString or self::gmx:Anchor]")
                );
            }
            return $array;
        }
        return null;
    }

    private static function getDoi(\SimpleXMLElement $node): ?array
    {
        $xpathExpression = './idf:doi[./*]';
        $tmpNode = IdfHelper::getNode($node, $xpathExpression);
        if (!empty($tmpNode)) {
            return array(
                array(
                    array(
                        "value" => IdfHelper::getNodeValue($tmpNode, "./id") ?? '',
                        "type" => "text"
                    ),
                    array(
                        "value" => IdfHelper::getNodeValue($tmpNode, "./type") ?? '',
                        "type" => "text"
                    )
                )
            );
        }
        return null;
    }

}
