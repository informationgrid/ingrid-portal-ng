<?php

namespace Grav\Plugin;

use Grav\Common\Utils;
use Grav\Common\Grav;

class DetailMetadataParserIdfUVP
{
    static string $xPathStringLength = '[string-length(text()) > 0]';

    public static function parse(\SimpleXMLElement $node, string $uuid, null|string $dataSourceName, array $providers, string $lang, Grav $grav ): array
    {
        echo "<script>console.log('InGrid Detail parse metadata with " . $uuid . "');</script>";

        $type = IdfHelper::getNodeValue($node, "./type" . self::$xPathStringLength);
        $title = IdfHelper::getNodeValue($node, "./name" . self::$xPathStringLength);
        $metadata = [
            "uuid" => $uuid,
            "parent_uuid" => IdfHelper::getNodeValue($node, "./parent_id" . self::$xPathStringLength),
            "type" => $type,
            "type_name" => CodelistHelper::getCodelistEntry(["8001"], $type, $lang),
            "title" => $title,
            "summary" => IdfHelper::getNodeValue($node, "./descr" . self::$xPathStringLength),
            "date" => IdfHelper::getNodeValue($node, "./date" . self::$xPathStringLength),
            "categories" => IdfHelper::getNodeValueList($node, "./uvpgs/uvpg[@category and string-length(@category)!=0]/@category"),
            "steps" => self::getSteps($node),
            "negative" => self::getNegative($node),
            "addresses" => self::getAddresses($node, $lang),
            "bbox" => self::getBBox($node, $title),
            "hasDocs" => count(IdfHelper::getNodeValueList($node,"//docs/doc")) > 0
        ];
        return $metadata;
    }

    private static function getBBox(\SimpleXMLElement $node, string $title): array
    {
        $array = [];
        $value = IdfHelper::getNodeValue($node, './spatialValue');
        if ($value) {
            $values = explode(':', $value);
            $coords = explode(', ', $values[1]);
            $array[] = array(
                "title" => empty($values[0]) ? $title : $values[0],
                "westBoundLongitude" => (float) $coords[0],
                "southBoundLatitude" => (float) $coords[1],
                "eastBoundLongitude" => (float) $coords[2],
                "northBoundLatitude" => (float) $coords[3],
            );
        }
        return $array;
    }
    private static function getSteps(\SimpleXMLElement $node): array
    {
        $array = [];
        $nodes = IdfHelper::getNodeList($node, "./steps/step");
        if (!empty($nodes)) {
            foreach ($nodes as $tmpNode) {
                $type = IdfHelper::getNodeValue($tmpNode, './@type');
                $dateFrom = IdfHelper::getNodeValue($tmpNode, './datePeriod/from');
                $dateTo = IdfHelper::getNodeValue($tmpNode, './datePeriod/to');
                $technicalDocs = self::getDocs($tmpNode, './docs[@type="technicalDocs"]/doc');
                $applicationDocs = self::getDocs($tmpNode, './docs[@type="applicationDocs"]/doc');
                $reportsRecommendationsDocs = self::getDocs($tmpNode, './docs[@type="reportsRecommendationsDocs"]/doc');
                $moreDocs = self::getDocs($tmpNode, './docs[@type="moreDocs"]/doc');
                $publicationDocs = self::getDocs($tmpNode, './docs[@type="publicationDocs"]/doc');
                $considerationDocs = self::getDocs($tmpNode, './docs[@type="considerationDocs"]/doc');
                $approvalDocs = self::getDocs($tmpNode, './docs[@type="approvalDocs"]/doc');
                $designDocs = self::getDocs($tmpNode, './docs[@type="designDocs"]/doc');
                $item = array(
                    'type' => $type,
                    'dateFrom' => $dateFrom,
                    'dateTo' => $dateTo,
                    'technicalDocs' => $technicalDocs,
                    'applicationDocs' => $applicationDocs,
                    'reportsRecommendationsDocs' => $reportsRecommendationsDocs,
                    'moreDocs' => $moreDocs,
                    'publicationDocs' => $publicationDocs,
                    'considerationDocs' => $considerationDocs,
                    'approvalDocs' => $approvalDocs,
                    'designDocs' => $designDocs,
                );
                $array[] = $item;
            }
        }
        return $array;
    }

    private static function getNegative(\SimpleXMLElement $node): array
    {
        $array = [];
        $dateFrom = IdfHelper::getNodeValue($node, './datePeriod/from');
        $uvpNegativeRelevantDocs = self::getDocs($node, './docs[@type="uvpNegativeRelevantDocs"]/doc');
        if ($dateFrom || $uvpNegativeRelevantDocs) {
            return array(
                'dateFrom' => $dateFrom,
                'uvpNegativeRelevantDocs' => $uvpNegativeRelevantDocs,
            );
        }
        return $array;
    }

    private static function getDocs(\SimpleXMLElement $node, string $xpath): array
    {
        $array = [];
        $nodes = IdfHelper::getNodeList($node, $xpath);
        foreach ($nodes as $tmpNode) {
            $label = IdfHelper::getNodeValue($tmpNode, './label');
            $link = IdfHelper::getNodeValue($tmpNode, './link');
            $item = array(
                'label' => $label,
                'link' => $link,
            );
            $array[] = $item;
        }
        return $array;
    }
    private static function getAddresses(\SimpleXMLElement $node, string $lang): array
    {
        $array = [];
        $nodes = IdfHelper::getNodeList($node, "./addresses/address");

        foreach ($nodes as $tmpNode) {
            $id = IdfHelper::getNodeValue($tmpNode, './@id');
            $name = IdfHelper::getNodeValue($tmpNode, './name');
            $parents = IdfHelper::getNodeValueList($tmpNode, './parent/name');
            $phone = IdfHelper::getNodeValue($tmpNode, './phone');
            $fax = IdfHelper::getNodeValue($tmpNode, './fax');
            $mail = IdfHelper::getNodeValue($tmpNode, './mail');
            $url = IdfHelper::getNodeValue($tmpNode, './url');
            $street = IdfHelper::getNodeValue($tmpNode, './street');
            $city = IdfHelper::getNodeValue($tmpNode, './city');
            $postalcode = IdfHelper::getNodeValue($tmpNode, './postalcode');
            $country = IdfHelper::getNodeValue($tmpNode, './country');
            $postbox = IdfHelper::getNodeValue($tmpNode, './postbox');
            $item = array (
                'id' => $id,
                'name' => $name,
                'parents' => $parents,
                'phone' => $phone,
                'fax' => $fax,
                'mail' => $mail,
                'url' => $url,
                'street' => $street,
                'city' => $city,
                'postalcode' => $postalcode,
                'country' => $country,
                'postbox' => $postbox,
            );
            $array[] = $item;
        }
        return $array;
    }
}
