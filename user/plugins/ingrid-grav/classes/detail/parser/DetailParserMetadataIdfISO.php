<?php

namespace Grav\Plugin;

use Grav\Common\Utils;
use Grav\Common\Grav;

class DetailParserMetadataIdfISO
{

    public static function parse(\SimpleXMLElement $node, string $uuid, null|string $dataSourceName, array $providers, string $lang): array
    {
        echo "<script>console.log('InGrid Detail parse metadata with " . $uuid . "');</script>";

        $type = self::getType($node);
        $metadata = [
            "uuid" => $uuid,
            "parent_uuid" => IdfHelper::getNodeValue($node, "./gmd:parentIdentifier/*[self::gco:CharacterString or self::gmx:Anchor]"),
            "type" => $type,
            "type_name" => CodelistHelper::getCodelistEntry(["8000"], $type, $lang),
            "title" => IdfHelper::getNodeValue($node, "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:title/*[self::gco:CharacterString or self::gmx:Anchor]"),
            "altTitle" => IdfHelper::getNodeValueListCodelistCompare($node, "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:alternateTitle/*[self::gco:CharacterString or self::gmx:Anchor]", ["8010"], $lang, false),
            "summary" => IdfHelper::getNodeValue($node, "./idf:abstract/*[self::gco:CharacterString or self::gmx:Anchor] | ./gmd:identificationInfo/*/gmd:abstract/*[self::gco:CharacterString or self::gmx:Anchor]"),
            "accessConstraint" => IdfHelper::getNodeValue($node, "./idf:hasAccessConstraint"),
            "previews" => self::getPreviews($node),
            "mapUrl" => self::getMapUrl($node, $type),
            "links" => self::getLinkRefs($node, $type, $lang),
            "contacts" => self::getContactRefs($node, $lang),
        ];
        self::getTimeRefs($node, $metadata, $lang);
        self::getMapRefs($node, $metadata, $lang);
        self::getUseRefs($node, $metadata, $lang);
        self::getInfoRefs($node, $type, $metadata, $lang);
        self::getDataQualityRefs($node, $metadata);
        self::getMetaInfoRefs($node, $uuid, $dataSourceName, $providers, $metadata, $lang);
        //var_dump($metadata);
        return $metadata;
    }

    private static function getPreviews(\SimpleXMLElement $node): array
    {
        $array = [];
        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:identificationInfo/*/gmd:graphicOverview/gmd:MD_BrowseGraphic");
        foreach ($tmpNodes as $tmpNode) {
            $map = [];
            $url = IdfHelper::getNodeValue($tmpNode, "./gmd:fileName/*[self::gco:CharacterString or self::gmx:Anchor]");
            $descr = IdfHelper::getNodeValue($tmpNode, "./gmd:fileDescription/*[self::gco:CharacterString or self::gmx:Anchor]");
            $map["url"] = $url;
            $map["descr"] = $descr;
            array_push($array, $map);
        }
        return $array;
    }

    private static function getType(\SimpleXMLElement $node): string
    {
        $hierachyLevel = "";
        $hierachyLevelNode = IdfHelper::getNode($node, "./gmd:hierarchyLevel/gmd:MD_ScopeCode");
        if ($hierachyLevelNode) {
            $hierachyLevel = IdfHelper::getNode($hierachyLevelNode, "./@codeListValue");
        }
        $hierachyLevelName = IdfHelper::getNodeValue($node, "./gmd:hierarchyLevelName/*[self::gco:CharacterString or self::gmx:Anchor]");
        if (strcasecmp($hierachyLevel, "service") == 0){
            return "3";
        } else if (strcasecmp($hierachyLevel, "application") == 0){
            return "6";
        } else if (strcasecmp($hierachyLevelName, "job") == 0 && strcasecmp($hierachyLevel, "nonGeographicDataset") == 0){
            return "0";
        } else if (strcasecmp($hierachyLevelName, "document") == 0 && strcasecmp($hierachyLevel, "nonGeographicDataset") == 0){
            return "2";
        } else if (strcasecmp($hierachyLevelName, "project") == 0 && strcasecmp($hierachyLevel, "nonGeographicDataset") == 0){
            return "4";
        } else if (strcasecmp($hierachyLevelName, "database") == 0 && strcasecmp($hierachyLevel, "nonGeographicDataset") == 0){
            return "5";
        } else if (strcasecmp($hierachyLevel, "dataset") == 0 || strcasecmp($hierachyLevel, "series") == 0){
            return "1";
        } else if (strcasecmp($hierachyLevel, "tile") == 0){
            // tile should be mapped to "Geoinformation/Karte" explicitly, see INGRID-2225
            return "1";
        } else {
            // Default to "Geoinformation/Karte", see INGRID-2225
            return "1";
        }
    }

    private static function getTimeRefs(\SimpleXMLElement $node, array &$metadata, string $lang): void
    {
        ## Durch die Ressource abgedeckte Zeitspanne
        $tmpValue = IdfHelper::getNodeValue($node, "./gmd:identificationInfo/*/*/gmd:EX_Extent/gmd:temporalElement/gmd:EX_TemporalExtent/gmd:extent/gml:TimeInstant/gml:timePosition");
        if ($tmpValue) {
            $metadata["time_atTime"] = $tmpValue;
        }

        $tmpValue = IdfHelper::getNodeValue($node, "./gmd:identificationInfo/*/*/gmd:EX_Extent/gmd:temporalElement/gmd:EX_TemporalExtent/gmd:extent/gml:TimePeriod/gml:beginPosition");
        if ($tmpValue) {
            $metadata["time_beginTime"] = $tmpValue;
        }

        $tmpValue = IdfHelper::getNodeValue($node, "./gmd:identificationInfo/*/*/gmd:EX_Extent/gmd:temporalElement/gmd:EX_TemporalExtent/gmd:extent/gml:TimePeriod/gml:endPosition");
        if ($tmpValue) {
            $metadata["time_endTime"] = $tmpValue;
        }

        $tmpValue = IdfHelper::getNodeValue($node, "./gmd:identificationInfo/*/*/gmd:EX_Extent/gmd:temporalElement/gmd:EX_TemporalExtent/gmd:extent/gml:TimePeriod/gml:endPosition/@indeterminatePosition");
        if ($tmpValue) {
            $metadata["time_fromType"] = $tmpValue;
        }

        ## Status
        $tmpValue = IdfHelper::getNodeValue($node, "./*/*/gmd:status/gmd:MD_ProgressCode/@codeListValue");
        if ($tmpValue) {
            $metadata["time_status"] = CodelistHelper::getCodelistEntryByIso(["523"], $tmpValue, $lang);
        }

        ## Periodizität
        $tmpValue = IdfHelper::getNodeValue($node, "./*/*/gmd:resourceMaintenance/gmd:MD_MaintenanceInformation/gmd:maintenanceAndUpdateFrequency/gmd:MD_MaintenanceFrequencyCode/@codeListValue");
        if ($tmpValue) {
            $metadata["time_period"] = CodelistHelper::getCodelistEntryByIso(["518"], $tmpValue, $lang);
        }

        ## Intervall der Erhebung
        $tmpValue = IdfHelper::getNodeValue($node, "./*/*/gmd:resourceMaintenance/gmd:MD_MaintenanceInformation/gmd:userDefinedMaintenanceFrequency/gts:TM_PeriodDuration");
        if ($tmpValue) {
            $metadata["time_interval"] = TMPeriodDurationHelper::transformPeriodDuration($tmpValue, $lang);
        }

        ## Erläuterung zum Zeitbezug
        $tmpValue = IdfHelper::getNodeValue($node, "./*/*/gmd:resourceMaintenance/gmd:MD_MaintenanceInformation/gmd:maintenanceNote/*[self::gco:CharacterString or self::gmx:Anchor]");
        if ($tmpValue) {
            $metadata["time_descr"] = $tmpValue;
        }

        ## Zeitbezug der Ressource
        $tmpValue = IdfHelper::getNode($node, "./*/*/gmd:citation/gmd:CI_Citation/gmd:date/gmd:CI_Date[./gmd:date]");
        if (!is_null($tmpValue)) {
            $tmpValue = IdfHelper::getNodeValue($node, "./*/*/gmd:citation/gmd:CI_Citation/gmd:date/gmd:CI_Date[gmd:dateType/gmd:CI_DateTypeCode/@codeListValue = 'creation']/gmd:date/*[self::gco:Date or self::gco:DateTime]");
            if ($tmpValue) {
                $metadata["time_creation"] = $tmpValue;
            }
            $tmpValue = IdfHelper::getNodeValue($node, "./*/*/gmd:citation/gmd:CI_Citation/gmd:date/gmd:CI_Date[gmd:dateType/gmd:CI_DateTypeCode/@codeListValue = 'publication']/gmd:date/*[self::gco:Date or self::gco:DateTime]");
            if ($tmpValue) {
                $metadata["time_publication"] = $tmpValue;
            }
            $tmpValue = IdfHelper::getNodeValue($node, "./*/*/gmd:citation/gmd:CI_Citation/gmd:date/gmd:CI_Date[gmd:dateType/gmd:CI_DateTypeCode/@codeListValue = 'revision']/gmd:date/*[self::gco:Date or self::gco:DateTime]");
            if ($tmpValue) {
                $metadata["time_revision"] = $tmpValue;
            }
        }

        $tmpValue = IdfHelper::getNodeValue($node, "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:report/gmd:DQ_AccuracyOfATimeMeasurement/gmd:result/gmd:DQ_QuantitativeResul/gmd:value/gco:Record");
        if ($tmpValue) {
            $metadata["time_measureValue"] = $tmpValue;
        }

        $tmpValue = IdfHelper::getNodeValue($node, "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:report/gmd:DQ_AccuracyOfATimeMeasurement/gmd:result/gmd:DQ_QuantitativeResul/gmd:valueUnit/gml:UnitDefinition/gml:catalogSymbol");
        if ($tmpValue) {
            $metadata["time_measureUnit"] = $tmpValue;
        }
    }

    private static function getMapRefs(\SimpleXMLElement $node, array &$metadata, string $lang): void
    {

        $config = Grav::instance()['config'];
        $geo_api_url = $config->get('plugins.ingrid-grav.geo_api.url');
        $geo_api_user = $config->get('plugins.ingrid-grav.geo_api.user');
        $geo_api_pass = $config->get('plugins.ingrid-grav.geo_api.pass');
        $geo_api = [
            'url' => $geo_api_url,
            'user' => $geo_api_user,
            'pass' => $geo_api_pass,
        ];

        $regionKey = IdfHelper::getNode($node, "./idf:regionKey");
        $loc_descr = IdfHelper::getNodeValue($node, "./gmd:identificationInfo/*/*/gmd:EX_Extent/gmd:description/*[self::gco:CharacterString or self::gmx:Anchor]");
        $polygon = IdfHelper::getNode($node, "./gmd:identificationInfo/*/*/gmd:EX_Extent/gmd:geographicElement/gmd:EX_BoundingPolygon/gmd:polygon/*");
        if ($polygon !== null) {
            $metadata["map_polygon_wkt"] = IdfHelper::transformGML($polygon, $geo_api, 'wkt');
            $metadata["map_polygon_geojson"] = IdfHelper::transformGML($polygon, $geo_api, 'geojson');
        }
        $metadata["map_regionKey"] = $regionKey;
        $metadata["map_loc_descr"] = $loc_descr;
        $metadata["map_bboxes"] = self::getBBoxes($node);
        $metadata["map_geographicElement"] = self::getGeographicElements($node, $lang);
        $metadata["map_areaHeight"] = self::getAreaHeight($node, $lang);
        $metadata["map_referencesystem_id"] = self::getReferences($node);
    }

    private static function getReferences(\SimpleXMLElement $node): array
    {
        $config = Grav::instance()['config'];
        $theme = $config->get('system.pages.theme');
        $reference_system_link = $config->get('themes.' . $theme . '.hit_detail.reference_system_link');
        $reference_system_link_replace = $config->get('themes.' . $theme . '.hit_detail.reference_system_link_replace');

        $array = [];
        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:referenceSystemInfo/gmd:MD_ReferenceSystem/gmd:referenceSystemIdentifier/gmd:RS_Identifier");
        foreach ($tmpNodes as $tmpNode) {
            $code = IdfHelper::getNodeValue($tmpNode, "./gmd:code/*[self::gco:CharacterString or self::gmx:Anchor]");
            $codeSpace = IdfHelper::getNodeValue($tmpNode, "./gmd:codeSpace/*[self::gco:CharacterString or self::gmx:Anchor]");
            $url = null;
            $title = null;
            if ($code && $codeSpace) {
                if (str_contains($code, "EPSG")) {
                    $title = $code;
                } else {
                    $title = $codeSpace . ":" . $code;
                }

            } else if ($codeSpace) {
                $title = $codeSpace;
            } else if ($code) {
                $title = $code;
            }
            if ($title) {
                if (str_starts_with($title, $reference_system_link_replace)) {
                    $title = str_replace($reference_system_link_replace, '', $title);
                }
                preg_match('#EPSG( |:)[0-9]*#', $title, $matches);
                foreach ($matches as $match) {
                    if (str_contains($match, "EPSG")) {
                        $epsg = filter_var($title, FILTER_SANITIZE_NUMBER_INT);
                        if (!empty($epsg)) {
                            $url = $reference_system_link . $epsg;
                            break;
                        }
                    }
                }
                $array[] = [
                    "title" => $title,
                    "url" => $url,
                ];
            }
        }
        return $array;
    }

    private static function getBBoxes(\SimpleXMLElement $node): array
    {
        $array = [];
        $geographicIdentifiers = [];
        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:identificationInfo/*/*/gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicDescription/gmd:geographicIdentifier/gmd:MD_Identifier/gmd:code/*[self::gco:CharacterString or self::gmx:Anchor]");
        foreach ($tmpNodes as $tmpNode) {
            $value = (string) IdfHelper::getNodeValue($tmpNode, ".");
            $geographicIdentifiers[] = $value;
        }

        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:identificationInfo/*/*/gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicBoundingBox");
        $count = 0;
        foreach ($tmpNodes as $tmpNode) {

            $value = count($geographicIdentifiers) > $count ? $geographicIdentifiers[$count] : "";
            $map = [];
            $map["title"] = $value;

            $map["westBoundLongitude"] = (float)IdfHelper::getNodeValue($tmpNode, "./gmd:westBoundLongitude/gco:Decimal");
            $map["southBoundLatitude"] = (float)IdfHelper::getNodeValue($tmpNode, "./gmd:southBoundLatitude/gco:Decimal");
            $map["eastBoundLongitude"] = (float)IdfHelper::getNodeValue($tmpNode, "./gmd:eastBoundLongitude/gco:Decimal");
            $map["northBoundLatitude"] = (float)IdfHelper::getNodeValue($tmpNode, "./gmd:northBoundLatitude/gco:Decimal");

            $array[] = $map;
            $count++;
        }
        return $array;
    }

    private static function getGeographicElements(\SimpleXMLElement $node, string $lang): array
    {
        $array = [];
        $geographicIdentifiers = [];
        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:identificationInfo/*/*/gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicDescription/gmd:geographicIdentifier/gmd:MD_Identifier/gmd:code/*[self::gco:CharacterString or self::gmx:Anchor]");
        foreach ($tmpNodes as $tmpNode) {
            $value = (string) IdfHelper::getNodeValue($tmpNode, ".");
            $geographicIdentifiers[] = $value;
        }

        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:identificationInfo/*/*/gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicBoundingBox");
        $count = 0;
        foreach ($tmpNodes as $tmpNode) {
            $item = [];

            $value = count($geographicIdentifiers) > $count ? $geographicIdentifiers[$count] : "";
            $map = [];
            $map["value"] = $value;
            $map["type"] = "text";
            $item[] = $map;

            $westBoundLongitude = (float)IdfHelper::getNodeValue($tmpNode, "./gmd:westBoundLongitude/gco:Decimal");
            $southBoundLatitude = (float)IdfHelper::getNodeValue($tmpNode, "./gmd:southBoundLatitude/gco:Decimal");
            $eastBoundLongitude = (float)IdfHelper::getNodeValue($tmpNode, "./gmd:eastBoundLongitude/gco:Decimal");
            $northBoundLatitude = (float)IdfHelper::getNodeValue($tmpNode, "./gmd:northBoundLatitude/gco:Decimal");

            $map = [];
            if ($westBoundLongitude && $southBoundLatitude) {
                $map["value"] = round($westBoundLongitude, 3) . "°/" . round($southBoundLatitude, 3) . "°";
                $map["type"] = "text";
            } else {
                $map["value"] = "";
                $map["type"] = "text";
            }
            $item[] = $map;

            $map = [];
            if ($eastBoundLongitude && $northBoundLatitude) {
                $map["value"] = round($eastBoundLongitude, 3) . "°/" . round($northBoundLatitude, 3) . "°";
                $map["type"] = "text";
            } else {
                $map["value"] = "";
                $map["type"] = "text";
            }
            $item[] = $map;

            $array[] = $item;
            $count++;
        }
        return $array;
    }

    private static function getAreaHeight(\SimpleXMLElement $node, string $lang): array
    {
        $array = [];
        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:identificationInfo/*/*/gmd:EX_Extent/gmd:verticalElement/gmd:EX_VerticalExtent");
        foreach ($tmpNodes as $tmpNode) {
            $item = [];

            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:minimumValue/gco:Real");
            $map = [];
            $map["value"] = $value;
            $map["type"] = "text";
            $item[] = $map;

            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:maximumValue/gco:Real");
            $map = [];
            $map["value"] = $value;
            $map["type"] = "text";
            $item[] = $map;

            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:verticalCRS/gml:VerticalCRS/gml:verticalCS/gml:VerticalCS/gml:axis/gml:CoordinateSystemAxis/@uom", ["102"], $lang);
            $map = [];
            $map["value"] = $value;
            $map["type"] = "text";
            $item[] = $map;

            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:verticalCRS/gml:VerticalCRS/gml:verticalDatum/gml:VerticalDatum/gml:name", ["101"], $lang);
            if(empty($value)) {
                $value = IdfHelper::getNodeValue($tmpNode, "./gmd:verticalCRS/gml:VerticalCRS/gml:verticalDatum/gml:VerticalDatum/gml:identifier", ["101"], $lang);
            }
            if(empty($value)) {
                $value = IdfHelper::getNodeValue($tmpNode, "./gmd:verticalCRS/gml:VerticalCRS/gml:name", ["101"], $lang);
            }
            $map = [];
            $map["value"] = $value;
            $map["type"] = "text";
            $item[] = $map;

            $array[] = $item;
        }
        return $array;
    }

    private static function getLinkRefs(\SimpleXMLElement $node, string $objType, string $lang): array
    {
        $array = [];

        // Querverweise
        $xpathExpression = "./idf:crossReference[not(@uuid=preceding::idf:crossReference/@uuid)]";
        $tmpNodes = IdfHelper::getNodeList($node, $xpathExpression);
        foreach ($tmpNodes as $tmpNode) {
            $uuid = IdfHelper::getNodeValue($tmpNode, "./@uuid");
            $title = IdfHelper::getNodeValue($tmpNode, "./idf:objectName");
            $description = IdfHelper::getNodeValue($tmpNode, "./idf:description");
            $type = IdfHelper::getNodeValue($tmpNode, "./idf:objectType");
            $previews = IdfHelper::getNodeValueList($tmpNode, "./idf:graphicOverview");
            $serviceVersion = IdfHelper::getNodeValue($tmpNode, "./idf:serviceVersion");
            $serviceType = IdfHelper::getNodeValue($tmpNode, "./idf:serviceType");
            $serviceUrl = IdfHelper::getNodeValue($tmpNode, "./idf:serviceUrl");
            $extMapUrl = IdfHelper::getNodeValue($tmpNode, "./idf:mapUrl");
            $attachedToField = IdfHelper::getNodeValue($tmpNode, "./idf:attachedToField");
            $item = array (
                "uuid" => $uuid,
                "title" => $title,
                "description" => $description,
                "type" => $type,
                "type_name" => CodelistHelper::getCodelistEntry(["8000"], $type, $lang),
                "previews" => $previews,
                "extMapUrl" => $extMapUrl,
                "attachedToField" => $type != "1" ? $attachedToField : null,
                "kind" => "object",
            );
            if ($serviceUrl) {
                $mapUrl = CapabilitiesHelper::getMapUrl($serviceUrl, $serviceVersion, $serviceType);
                if ($mapUrl) {
                    $item["mapUrl"] = $mapUrl;
                }
            }
            if ($serviceType || $serviceVersion) {
                $service = CapabilitiesHelper::getHitServiceType($serviceVersion, $serviceType);
                if ($service) {
                    $item["serviceType"] = $service;
                }
            }

            $array[] = $item;
        }

        // Querverweise
        $xpathExpression = "./gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine[not(./*/idf:attachedToField[@entry-id='9990']) and not(./*/gmd:function/*/@codeListValue='download') and (./*/gmd:applicationProfile/*[self::gco:CharacterString or self::gmx:Anchor]='coupled')]";
        if ($objType == "1")
            $xpathExpression = "./gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine[not(./*/idf:attachedToField[@entry-id='9990']) and not(./*/gmd:function/*/@codeListValue='download') and not(./*/*/gmd:URL[contains(translate(text(),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'), 'getcap')]) and (./*/gmd:applicationProfile/*[self::gco:CharacterString or self::gmx:Anchor]='coupled')]";

        $tmpNodes = IdfHelper::getNodeList($node, $xpathExpression);
        foreach ($tmpNodes as $tmpNode) {
            $title = IdfHelper::getNodeValue($tmpNode, "./*/gmd:name/*[self::gco:CharacterString or self::gmx:Anchor]");
            $description = IdfHelper::getNodeValue($tmpNode, "./*/gmd:description/*[self::gco:CharacterString or self::gmx:Anchor]");
            $cswUrl = IdfHelper::getNodeValue($tmpNode, "./*/gmd:linkage/gmd:URL");
            $attachedToField = IdfHelper::getNodeValue($tmpNode, "./*/idf:attachedToField");
            $applicationProfile = IdfHelper::getNodeValue($tmpNode, "./*/gmd:applicationProfile/*[self::gco:CharacterString or self::gmx:Anchor]");
            $type = "1";
            $item = array (
                "title" => $title ?? $cswUrl,
                "description" => $description,
                "type" => $type,
                "type_name" => CodelistHelper::getCodelistEntry(["8000"], $type, $lang),
                "cswUrl" => $cswUrl,
                //attachedToField" => $attachedToField,
                "applicationProfile" => $applicationProfile,
                "kind" => "object",
            );
            $array[] = $item;
        }

        // Weitere Verweise
        $xpathExpression = "./gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine[not(./*/idf:attachedToField[@entry-id='9990']) and not(./*/gmd:function/*/@codeListValue='download')]";
        $tmpNodes = IdfHelper::getNodeList($node, $xpathExpression);
        foreach ($tmpNodes as $tmpNode) {
            $url = IdfHelper::getNodeValue($tmpNode, "./*/gmd:linkage/gmd:URL");
            $title = IdfHelper::getNodeValue($tmpNode, "./*/gmd:name/*[self::gco:CharacterString or self::gmx:Anchor]");
            $description = IdfHelper::getNodeValue($tmpNode, "./*/gmd:description/*[self::gco:CharacterString or self::gmx:Anchor]");
            $attachedToField = IdfHelper::getNodeValue($tmpNode, "./*/idf:attachedToField");
            $applicationProfile = IdfHelper::getNodeValue($tmpNode, "./*/gmd:applicationProfile/*[self::gco:CharacterString or self::gmx:Anchor]");
            $size = IdfHelper::getNodeValue($tmpNode, "./../gmd:transferSize/gco:Real");
            $item = array (
                "url" => $url,
                "title" => $title ?? $url,
                "description" => $description,
                "attachedToField" => $attachedToField,
                "applicationProfile" => $applicationProfile,
                "linkInfo" => $size ? "[" . $size . "MB]" : null,
                "kind" => "other",
            );
            $array[] = $item;
        }

        // Download
        $xpathExpression = "./gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine[./*/idf:attachedToField[@entry-id='9990'] or ./*/gmd:function/*/@codeListValue='download']";
        $tmpNodes = IdfHelper::getNodeList($node, $xpathExpression);
        foreach ($tmpNodes as $tmpNode) {
            $url = IdfHelper::getNodeValue($tmpNode, "./*/gmd:linkage/gmd:URL");
            $title = IdfHelper::getNodeValue($tmpNode, "./*/gmd:name/*[self::gco:CharacterString or self::gmx:Anchor]");
            $description = IdfHelper::getNodeValue($tmpNode, "./*/gmd:description/*[self::gco:CharacterString or self::gmx:Anchor]");
            $attachedToField = IdfHelper::getNodeValue($tmpNode, "./*/idf:attachedToField");
            $applicationProfile = IdfHelper::getNodeValue($tmpNode, "./*/gmd:applicationProfile/*[self::gco:CharacterString or self::gmx:Anchor]");
            $size = IdfHelper::getNodeValue($tmpNode, "./../gmd:transferSize/gco:Real");
            $item = array (
                "url" => $url,
                "title" => $title ?? $url,
                "description" => $description,
                "attachedToField" => $attachedToField,
                "applicationProfile" => $applicationProfile,
                "linkInfo" => $size ? "[" . $size . "MB]" : null,
                "kind" => "download",
            );
            $array[] = $item;
        }

        // Übergeordnete Objekte
        $xpathExpression = "./idf:superiorReference";
        $tmpNodes = IdfHelper::getNodeList($node, $xpathExpression);
        foreach ($tmpNodes as $tmpNode) {
            $uuid = IdfHelper::getNodeValue($tmpNode, "./@uuid");
            $title = IdfHelper::getNodeValue($tmpNode, "./idf:objectName");
            $description = IdfHelper::getNodeValue($tmpNode, "./idf:description");
            $type = IdfHelper::getNodeValue($tmpNode, "./idf:objectType");
            $previews = IdfHelper::getNodeValueList($tmpNode, "./idf:graphicOverview");
            $extMapUrl = IdfHelper::getNodeList($tmpNode, "./idf:extMapUrl");
            $mapUrl = IdfHelper::getNodeList($tmpNode, "./idf:mapUrl");
            $item = array (
                "uuid" => $uuid,
                "title" => $title,
                "description" => $description,
                "type" => $type,
                "type_name" => CodelistHelper::getCodelistEntry(["8000"], $type, $lang),
                "previews" => $previews,
                "extMapUrl" => $extMapUrl,
                "mapUrl" => $mapUrl,
                "kind" => "superior",
            );
            $array[] = $item;
        }

        // Untergeordnete Objekte
        $xpathExpression = "./idf:subordinatedReference";
        $tmpNodes = IdfHelper::getNodeList($node, $xpathExpression);
        foreach ($tmpNodes as $tmpNode) {
            $uuid = IdfHelper::getNodeValue($tmpNode, "./@uuid");
            $title = IdfHelper::getNodeValue($tmpNode, "./idf:objectName");
            $description = IdfHelper::getNodeValue($tmpNode, "./idf:description");
            $type = IdfHelper::getNodeValue($tmpNode, "./idf:objectType");
            $previews = IdfHelper::getNodeList($tmpNode, "./idf:graphicOverview");
            $extMapUrl = IdfHelper::getNodeList($tmpNode, "./idf:extMapUrl");
            $mapUrl = IdfHelper::getNodeList($tmpNode, "./idf:mapUrl");
            $item = array (
                "uuid" => $uuid,
                "title" => $title,
                "description" => $description,
                "type" => $type,
                "type_name" => CodelistHelper::getCodelistEntry(["8000"], $type, $lang),
                "previews" => $previews,
                "extMapUrl" => $extMapUrl,
                "mapUrl" => $mapUrl,
                "kind" => "subordinated",
            );
            $array[] = $item;
        }

        // URL des Zuganges
        if ($objType == 3) {
            $xpathExpression = "./gmd:identificationInfo/*/srv:containsOperations/srv:SV_OperationMetadata[./srv:operationName/*[self::gco:CharacterString or self::gmx:Anchor][contains(translate(text(),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'), 'getcap')]]/srv:connectPoint";
            $tmpNodes = IdfHelper::getNodeList($node, $xpathExpression);
            if (empty($tmpNodes)) {
                $xpathExpression = "./gmd:identificationInfo/*/srv:containsOperations/srv:SV_OperationMetadata/srv:connectPoint";
                $tmpNodes = IdfHelper::getNodeList($node, $xpathExpression);
            }
            foreach ($tmpNodes as $tmpNode) {
                $url = IdfHelper::getNodeValue($tmpNode, "./*/gmd:linkage/gmd:URL");
                $title = IdfHelper::getNodeValue($tmpNode, "./*/gmd:linkage/gmd:URL");
                $description = IdfHelper::getNodeValue($tmpNode, "./../srv:operationDescription/*[self::gco:CharacterString or self::gmx:Anchor]");
                $item = array (
                    "url" => $url,
                    "title" => $title,
                    "description" => $description,
                    "kind" => "access",
                );
                $array[] = $item;
            }
        } else if ($objType == 6) {
            $xpathExpression = "./gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine[./*/idf:attachedToField[@entry-id='5066']]";
            $tmpNodes = IdfHelper::getNodeList($node, $xpathExpression);
            foreach ($tmpNodes as $tmpNode) {
                $url = IdfHelper::getNodeValue($tmpNode, "./*/gmd:linkage/gmd:URL");
                $title = IdfHelper::getNodeValue($tmpNode, "./*/gmd:name/*[self::gco:CharacterString or self::gmx:Anchor]");
                $description = IdfHelper::getNodeValue($tmpNode, "./*/gmd:description/*[self::gco:CharacterString or self::gmx:Anchor]");
                $attachedToField = IdfHelper::getNodeValue($tmpNode, "./*/idf:attachedToField");
                $item = array (
                    "url" => $url,
                    "title" => $title,
                    "description" => $description,
                    "attachedToField" => $attachedToField,
                    "kind" => "access",
                );
                $array[] = $item;
            }
        }
        return Utils::sortArrayByKey($array, "title", SORT_ASC);
    }

    private static function getUseRefs(\SimpleXMLElement$node, array &$metadata, string $lang): void
    {
        $metadata["use_useConstraints"] = self::getUseConstraints($node, $lang);
        $metadata["use_accessConstraints"] = self::getAccessConstraints($node);
        $metadata["use_useLimitations"] = self::getUseLimitations($node);
    }

    private static function getUseConstraints(\SimpleXMLElement $node, string $lang): array
    {
        $array = [];
        $constraints = [];
        $restriction = null;

        $nodes = IdfHelper::getNodeList($node, "./gmd:identificationInfo/*/gmd:resourceConstraints/gmd:MD_LegalConstraints[./gmd:useConstraints]");
        foreach ($nodes as $tmpNode) {
            $restriction = IdfHelper::getNodeValue($tmpNode, "./gmd:useConstraints/gmd:MD_RestrictionCode[not(contains(@codeListValue, 'otherRestrictions'))]", ["524"], $lang);

            $values = IdfHelper::getNodeValueList($tmpNode, "./gmd:otherConstraints/*[self::gco:CharacterString or self::gmx:Anchor][starts-with(text(),'{')]");
            foreach ($values as $value) {
                $constraints[] = self::removeConstraintPrefix($value);
            }
        }
        foreach ($nodes as $tmpNode) {
            $restriction = IdfHelper::getNodeValue($tmpNode, "./gmd:useConstraints/gmd:MD_RestrictionCode[not(contains(@codeListValue, 'otherRestrictions'))]", ["524"], $lang);

            $values = IdfHelper::getNodeValueList($tmpNode, "./gmd:otherConstraints/*[self::gco:CharacterString or self::gmx:Anchor][not(starts-with(text(),'{'))]");
            foreach ($values as $value) {
                $exists = false;
                foreach ($constraints as $constraint) {
                    $value = str_replace('Quellenvermerk: ', '', $value);
                    if (str_contains($constraint, $value) or str_contains($constraint, "\"" . $value . "\"")) {
                        $exists = true;
                        break;
                    }
                }
                if(!$exists) {
                    $constraints[] = self::removeConstraintPrefix($value);
                }
            }
        }
        if ($restriction) {
            $array['restriction'] = $restriction;
        }
        if (!empty($constraints)) {
            $array['constraints'] = $constraints;
        }
        return $array;
    }

    private static function removeConstraintPrefix(string $value): string
    {
        if (str_starts_with($value, "Nutzungseinschränkungen:")) {
            $value = str_replace("Nutzungseinschränkungen:", "", $value);
        }
        if (str_starts_with($value, "Nutzungsbedingungen:")) {
            $value = str_replace("Nutzungsbedingungen:", "", $value);
        }
        return $value;
    }

    private static function getAccessConstraints(\SimpleXMLElement $node): array
    {
        $array = [];
        $constraints = [];
        $nodes = IdfHelper::getNodeList($node, "./gmd:identificationInfo/*/gmd:resourceConstraints/gmd:MD_LegalConstraints[./gmd:accessConstraints]");
        foreach ($nodes as $tmpNode) {
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:accessConstraints/gmd:MD_RestrictionCode[not(contains(@codeListValue, 'otherRestrictions'))]");
            if ($value) {
                if (!in_array($value, $constraints)) {
                    $constraints[] = $value;
                }
            } else {
                $value = IdfHelper::getNodeValue($tmpNode, "./gmd:otherConstraints/*[self::gco:CharacterString or self::gmx:Anchor][not(starts-with(text(),'{'))]");
                if ($value) {
                    if (!in_array($value, $constraints)) {
                        $constraints[] = $value;
                    }
                }
            }
        }
        if (!empty($constraints)) {
            $array["constraints"] = $constraints;
        }
        return $array;
    }

    private static function getUseLimitations(\SimpleXMLElement $node): array
    {
        $array = [];
        $constraints = [];
        $nodes = IdfHelper::getNodeList($node, "./gmd:identificationInfo/*/gmd:resourceConstraints/gmd:MD_LegalConstraints/gmd:useLimitation");
        foreach ($nodes as $tmpNode) {
            $value = IdfHelper::getNodeValue($tmpNode, "./*[self::gco:CharacterString or self::gmx:Anchor]");
            if ($value) {
                if (!in_array($value, $constraints)) {
                    $constraints[] = self::removeConstraintPrefix($value);
                }
            }
        }
        if (!empty($constraints)) {
            $array["constraints"] = $constraints;
        }
        return $array;
    }

    private static function getContactRefs(\SimpleXMLElement $node, string $lang): array
    {
        $array = [];
        $nodes = IdfHelper::getNodeList($node, "./gmd:identificationInfo/*/gmd:pointOfContact/*");

        foreach ($nodes as $tmpNode) {
            $uuid = "";
            $type = "";
            $role = "";
            $roleNode = IdfHelper::getNode($tmpNode, "./gmd:role/gmd:CI_RoleCode");
            if (!is_null($roleNode)) {
                $role = IdfHelper::getNodeValue($roleNode, "./@codeListValue");
            }
            $addresses = [];
            $tmpAddresses = IdfHelper::getNodeList($tmpNode, "./idf:hierarchyParty");
            if(empty($tmpAddresses)) {
                $tmpAddresses = IdfHelper::getNodeList($tmpNode, ".");
            }

            foreach ($tmpAddresses as $tmpAddress) {
                $uuid = IdfHelper::getNodeValue($tmpAddress, "./@uuid");
                $type = IdfHelper::getNodeValue($tmpAddress, "./idf:addressType");
                $title = IdfHelper::getNodeValue($tmpAddress, "./idf:addressIndividualName | ./gmd:individualName/*[self::gco:CharacterString or self::gmx:Anchor]");
                if (is_null($title)) {
                    $title = IdfHelper::getNodeValue($tmpAddress, "./idf:addressOrganisationName | ./gmd:organisationName/*[self::gco:CharacterString or self::gmx:Anchor]");
                }
                if (!is_null($title)) {
                    $tmpSplitTitle = explode(',', $title);
                    $tmpIndex = count($tmpSplitTitle);
                    $newTitle = "";
                    while($tmpIndex) {
                        $newTitle = $newTitle ." ". $tmpSplitTitle[--$tmpIndex];
                    }
                    $title = $newTitle;
                }
                $individualName = IdfHelper::getNodeValue($tmpAddress, "./idf:addressIndividualName | ./gmd:individualName/*[self::gco:CharacterString or self::gmx:Anchor]");
                $organisationName = IdfHelper::getNodeValue($tmpAddress, "./idf:addressOrganisationName | ./gmd:organisationName/*[self::gco:CharacterString or self::gmx:Anchor]");
                $positionName = IdfHelper::getNodeValue($tmpAddress, "./gmd:positionName/*[self::gco:CharacterString or self::gmx:Anchor]");
                $item = array (
                    "uuid" => $uuid,
                    "type" => $type,
                    "title" => $title,
                    "individualName" => $individualName,
                    "organisationName" => $organisationName,
                    "positionName" => $positionName,
                );
                $addresses[] = $item;
            }

            $streets = [];
            $tmpStreets = IdfHelper::getNodeList($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:deliveryPoint/*[self::gco:CharacterString or self::gmx:Anchor]/text()") ?? [];
            foreach ($tmpStreets as $tmpStreet) {
                $tmpArray = explode(',', $tmpStreet);
                foreach ($tmpArray as $tmp) {
                    if (str_starts_with($tmpStreet, 'Postbox ')) {
                        $tmp = str_replace('Postbox ', 'Postfach ', $tmp);
                        $streets[] = $tmp;
                    } else {
                        $streets[] = $tmp;
                    }
                }
            }
            $postcode = IdfHelper::getNodeValue($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:postalCode/*[self::gco:CharacterString or self::gmx:Anchor]");
            $city = IdfHelper::getNodeValue($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:city/*[self::gco:CharacterString or self::gmx:Anchor]");
            $country = IdfHelper::getNodeValue($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:country/*[self::gco:CharacterString or self::gmx:Anchor]");
            $mail = IdfHelper::getNodeValue($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:electronicMailAddress/*[self::gco:CharacterString or self::gmx:Anchor]");
            $phone = IdfHelper::getNodeValue($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:phone/gmd:CI_Telephone/gmd:voice/*[self::gco:CharacterString or self::gmx:Anchor]");
            $facsimile = IdfHelper::getNodeValue($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:phone/gmd:CI_Telephone/gmd:facsimile/*[self::gco:CharacterString or self::gmx:Anchor]");
            $url = IdfHelper::getNodeValue($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:onlineResource/gmd:CI_OnlineResource/gmd:linkage/gmd:URL");
            $service_time = IdfHelper::getNodeValue($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:hoursOfService/*[self::gco:CharacterString or self::gmx:Anchor]");

            $item = array (
                "uuid" => $uuid,
                "type" => $type,
                "role" => $role,
                "role_name" => CodelistHelper::getCodelistEntryByIso(["505"], $role, $lang),
                "addresses" => $addresses,
                "streets" => $streets,
                "postcode" => $postcode,
                "city" => $city,
                "country" => $country ? CountryHelper::getNameFromCode($country, $lang): null,
                "mail" => $mail,
                "phone" => $phone,
                "facsimile" => $facsimile,
                "url" => $url,
                "service_time" => $service_time,
            );
            $array[] = $item;
        }
        return $array;
    }

    private static function getInfoRefs(\SimpleXMLElement$node, string $type, array &$metadata, string $lang): void
    {

        $array = [];
        $xpathExpression = "./gmd:identificationInfo/*/gmd:descriptiveKeywords/gmd:MD_Keywords[gmd:thesaurusName/gmd:CI_Citation/gmd:title/*[self::gco:CharacterString or self::gmx:Anchor][contains(text(), 'Service')]]/gmd:keyword/*[self::gco:CharacterString or self::gmx:Anchor]";
        self::addToArray($array, "classifications", IdfHelper::getNodeValueList($node, $xpathExpression, ["5200"], $lang));
        $xpathExpression = "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:lineage/gmd:LI_Lineage/gmd:statement/*[self::gco:CharacterString or self::gmx:Anchor]";
        self::addToArray($array, "lineageStatement", IdfHelper::getNodeValue($node, $xpathExpression));
        $xpathExpression = "./gmd:identificationInfo/*/srv:serviceType/gco:LocalName";
        self::addToArray($array, "serviceType", IdfHelper::getNodeValue($node, $xpathExpression, ["5100"], $lang));
        $xpathExpression = "./gmd:identificationInfo/*/srv:serviceTypeVersion/*[self::gco:CharacterString or self::gmx:Anchor]";
        self::addToArray($array, "serviceTypeVersions", IdfHelper::getNodeValueList($node, $xpathExpression));
        self::addToArray($array, "resolutions", self::getResolutions($node));
        $xpathExpression = "./gmd:identificationInfo/*/gmd:environmentDescription/*[self::gco:CharacterString or self::gmx:Anchor]";
        self::addToArray($array, "environmentDescription", IdfHelper::getNodeValue($node, $xpathExpression));
        $xpathExpression = "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:lineage/gmd:LI_Lineage/gmd:processStep/gmd:LI_ProcessStep/gmd:description/*[self::gco:CharacterString or self::gmx:Anchor]";
        self::addToArray($array, "processStepDescription", IdfHelper::getNodeValue($node, $xpathExpression));
        $xpathExpression = "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:lineage/gmd:LI_Lineage/gmd:source/gmd:LI_Source/gmd:description/*[self::gco:CharacterString or self::gmx:Anchor]";
        self::addToArray($array, "sourceDescription", IdfHelper::getNodeValue($node, $xpathExpression));
        $xpathExpression = "./gmd:identificationInfo/*/gmd:supplementalInformation/*[self::gco:CharacterString or self::gmx:Anchor]";
        self::addToArray($array, "supplementalInformation", IdfHelper::getNodeValue($node, $xpathExpression));
        $xpathExpression = "./gmd:identificationInfo/*/gmd:supplementalInformation/gmd:abstract";
        self::addToArray($array, "supplementalInformationAbstract", IdfHelper::getNodeValue($node, $xpathExpression));
        self::addToArray($array, "operations", self::getOperations($node));
        $xpathExpression = "./gmd:identificationInfo/*/srv:containsOperations/srv:SV_OperationMetadata/srv:connectPoint/gmd:CI_OnlineResource/gmd:linkage/gmd:URL";
        self::addToArray($array, "operationConnectPoint", IdfHelper::getNodeValue($node, $xpathExpression));

        $xpathExpression = "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:identifier/*/gmd:code/*[self::gco:CharacterString or self::gmx:Anchor]";
        self::addToArray($array, "identifierCode", IdfHelper::getNodeValue($node, $xpathExpression));
        $xpathExpression = "./gmd:identificationInfo/*/gmd:spatialRepresentationType/gmd:MD_SpatialRepresentationTypeCode/@codeListValue";
        self::addToArray($array, "spatialRepresentations", IdfHelper::getNodeValueList($node, $xpathExpression, ["526"], $lang));
        $xpathExpression = "./gmd:contentInfo/gmd:MD_FeatureCatalogueDescription/gmd:includedWithDataset/gco:Boolean";
        self::addToArray($array, "includedWithDataset", IdfHelper::getNodeValue($node, $xpathExpression));
        $xpathExpression = "./gmd:contentInfo/gmd:MD_FeatureCatalogueDescription/gmd:featureTypes/gco:LocalName";
        self::addToArray($array, "featureTypes", IdfHelper::getNodeValueList($node, $xpathExpression));
        self::addToArray($array, "featureCatalogues", self::getFeatureCatalogues($node));
        self::addToArray($array, "symbolCatalogues", self::getSymbolCatalogues($node));
        self::addToArray($array, "vectors", self::getVectors($node, $lang));

        $xpathExpression = "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:citedResponsibleParty[./gmd:CI_ResponsibleParty/gmd:role/gmd:CI_RoleCode/@codeListValue = 'originator']/gmd:CI_ResponsibleParty/gmd:individualName/*[self::gco:CharacterString or self::gmx:Anchor]";
        self::addToArray($array, "literatur_autor", IdfHelper::getNodeValue($node, $xpathExpression));
        $xpathExpression = "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:citedResponsibleParty[./gmd:CI_ResponsibleParty/gmd:role/gmd:CI_RoleCode/@codeListValue = 'resourceProvider']/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:contactInstructions/*[self::gco:CharacterString or self::gmx:Anchor]";
        self::addToArray($array, "literatur_loc", IdfHelper::getNodeValue($node, $xpathExpression));
        $xpathExpression = "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:citedResponsibleParty[./gmd:CI_ResponsibleParty/gmd:role/gmd:CI_RoleCode/@codeListValue = 'publisher']/gmd:CI_ResponsibleParty/gmd:individualName/*[self::gco:CharacterString or self::gmx:Anchor]";
        self::addToArray($array, "literatur_publisher", IdfHelper::getNodeValue($node, $xpathExpression));
        $xpathExpression = "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:citedResponsibleParty[./gmd:CI_ResponsibleParty/gmd:role/gmd:CI_RoleCode/@codeListValue = 'publisher']/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:city/*[self::gco:CharacterString or self::gmx:Anchor]";
        self::addToArray($array, "literatur_publish_loc", IdfHelper::getNodeValue($node, $xpathExpression));
        $xpathExpression = "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:citedResponsibleParty[./gmd:CI_ResponsibleParty/gmd:role/gmd:CI_RoleCode/@codeListValue = 'distribute']/gmd:CI_ResponsibleParty/gmd:organisationName/*[self::gco:CharacterString or self::gmx:Anchor]";
        self::addToArray($array, "literatur_publishing", IdfHelper::getNodeValue($node, $xpathExpression));
        $xpathExpression = "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:series/gmd:CI_Series/gmd:name/*[self::gco:CharacterString or self::gmx:Anchor]";
        self::addToArray($array, "literatur_publish_in", IdfHelper::getNodeValue($node, $xpathExpression));
        $xpathExpression = "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:editionDate/gco:Date";
        self::addToArray($array, "literatur_publish_year", IdfHelper::getNodeValue($node, $xpathExpression));
        $xpathExpression = "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:series/gmd:CI_Series/gmd:issueIdentification/*[self::gco:CharacterString or self::gmx:Anchor]";
        self::addToArray($array, "literatur_volume", IdfHelper::getNodeValue($node, $xpathExpression));
        $xpathExpression = "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:series/gmd:CI_Series/gmd:page/*[self::gco:CharacterString or self::gmx:Anchor]";
        self::addToArray($array, "literatur_sides", IdfHelper::getNodeValue($node, $xpathExpression));
        $xpathExpression = "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:ISBN/*[self::gco:CharacterString or self::gmx:Anchor]";
        self::addToArray($array, "literatur_isbn", IdfHelper::getNodeValue($node, $xpathExpression));
        $xpathExpression = "./gmd:identificationInfo/*/gmd:resourceFormat/gmd:MD_Format/gmd:name/*[self::gco:CharacterString or self::gmx:Anchor]";
        self::addToArray($array, "literatur_typ", IdfHelper::getNodeValue($node, $xpathExpression));
        $xpathExpression = "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:lineage/gmd:LI_Lineage/gmd:source/gmd:LI_Source/gmd:description/*[self::gco:CharacterString or self::gmx:Anchor]";
        self::addToArray($array, "literatur_base", IdfHelper::getNodeValue($node, $xpathExpression));
        $xpathExpression = "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:otherCitationDetails/*[self::gco:CharacterString or self::gmx:Anchor]";
        self::addToArray($array, "literatur_doc_info", IdfHelper::getNodeValue($node, $xpathExpression));
        $xpathExpression = "./gmd:identificationInfo/*/gmd:supplementalInformation/*[self::gco:CharacterString or self::gmx:Anchor]";
        self::addToArray($array, "literatur_description", IdfHelper::getNodeValue($node, $xpathExpression));

        $xpathExpression = "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:citedResponsibleParty[./gmd:CI_ResponsibleParty/gmd:role/gmd:CI_RoleCode/@codeListValue = 'projectManager']/gmd:CI_ResponsibleParty/gmd:individualName/*[self::gco:CharacterString or self::gmx:Anchor]";
        self::addToArray($array, "project_leader", IdfHelper::getNodeValue($node, $xpathExpression));
        $xpathExpression = "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:citedResponsibleParty[./gmd:CI_ResponsibleParty/gmd:role/gmd:CI_RoleCode/@codeListValue = 'projectParticipant']/gmd:CI_ResponsibleParty/gmd:individualName/*[self::gco:CharacterString or self::gmx:Anchor]";
        self::addToArray($array, "project_member", IdfHelper::getNodeValue($node, $xpathExpression));
        $xpathExpression = "./gmd:identificationInfo/*/gmd:supplementalInformation/*[self::gco:CharacterString or self::gmx:Anchor]";
        self::addToArray($array, "project_description", IdfHelper::getNodeValue($node, $xpathExpression));


        $xpathExpression = "./gmd:contentInfo/gmd:MD_FeatureCatalogueDescription/gmd:featureTypes/gco:LocalName";
        self::addToArray($array, "data_para", IdfHelper::getNodeValue($node, $xpathExpression));
        $xpathExpression = "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:lineage/gmd:LI_Lineage/gmd:source/gmd:LI_Source/gmd:description/*[self::gco:CharacterString or self::gmx:Anchor]";
        self::addToArray($array, "data_base", IdfHelper::getNodeValue($node, $xpathExpression));
        $xpathExpression = "./gmd:identificationInfo/*/gmd:supplementalInformation/*[self::gco:CharacterString or self::gmx:Anchor]";
        self::addToArray($array, "data_description", IdfHelper::getNodeValue($node, $xpathExpression));

        $metadata["info"] = $array;

        $publish_id = IdfHelper::getNodeValue($node, "./gmd:identificationInfo/*/gmd:resourceConstraints/gmd:MD_SecurityConstraints/gmd:classification/gmd:MD_ClassificationCode/@codeListValue");
        $usage = IdfHelper::getNode($node, "./gmd:identificationInfo/*/gmd:resourceSpecificUsage/gmd:MD_Usage/gmd:specificUsage/*[self::gco:CharacterString or self::gmx:Anchor]");
        $purpose = IdfHelper::getNode($node, "./gmd:identificationInfo/*/gmd:purpose/*[self::gco:CharacterString or self::gmx:Anchor]");
        $legal_basis = IdfHelper::getNodeList($node, "./gmd:identificationInfo/*/gmd:descriptiveKeywords/gmd:MD_Keywords[gmd:thesaurusName/gmd:CI_Citation/gmd:title/*[self::gco:CharacterString or self::gmx:Anchor]='Further legal basis']/gmd:keyword/*[self::gco:CharacterString or self::gmx:Anchor]");
        $export_criteria = IdfHelper::getNodeList($node, "./idf:exportCriteria");
        $language_code = IdfHelper::getNodeValueList($node, "./gmd:identificationInfo/*/gmd:language/gmd:LanguageCode/@codeListValue");
        $conformity = self::getConformities($node, $lang);
        $dataformat = self::getDataformats($node);
        $geodatalink = IdfHelper::getNodeValue($node, "./gmd:identificationInfo/*/gmd:resourceConstraints/gmd:MD_SecurityConstraints/gmd:classification/gmd:MD_ClassificationCode/@codeListValue");
        $media = self::getMedias($node);
        $order_instructions = IdfHelper::getNode($node, "./gmd:distributionInfo/gmd:MD_Distribution/gmd:distributor/gmd:MD_Distributor/gmd:distributionOrderProcess/gmd:MD_StandardOrderProcess/gmd:orderingInstructions/*[self::gco:CharacterString or self::gmx:Anchor]");

        self::addToArray($metadata, "info_additional_publish_id", $publish_id);
        self::addToArray($metadata, "info_additional_usage", $usage);
        self::addToArray($metadata, "info_additional_purpose", $purpose);
        self::addToArray($metadata, "info_additional_legal_basis", $legal_basis);
        self::addToArray($metadata, "info_additional_export_criteria", $export_criteria);
        self::addToArray($metadata, "info_additional_language_code", $language_code ? LanguageHelper::getNamesFromIso639_2($language_code, $lang) : null);
        self::addToArray($metadata, "info_additional_conformity", $conformity);
        self::addToArray($metadata, "info_additional_dataformat", $dataformat);
        self::addToArray($metadata, "info_additional_geodatalink", $geodatalink);
        self::addToArray($metadata, "info_additional_media", $media);
        self::addToArray($metadata, "info_additional_order_instructions", $order_instructions);

        [$inspire_themes, $priority_dataset, $spatial_scope, $gemet_concepts, $invekos, $hvd, $search_terms] = self::getKeywords($node, $lang);

        $adv_group = IdfHelper::getNodeValueListCodelistCompare($node, "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:alternateTitle/*[self::gco:CharacterString or self::gmx:Anchor]", ["8010"], $lang);
        $topic_category = IdfHelper::getNodeValueList($node, "./gmd:identificationInfo/*/gmd:topicCategory/gmd:MD_TopicCategoryCode", ["527"], $lang);

        self::addToArray($metadata, "info_keywords_inspire_themes", $inspire_themes);
        self::addToArray($metadata, "info_keywords_priority_dataset", $priority_dataset);
        self::addToArray($metadata, "info_keywords_spatial_scope", $spatial_scope);
        self::addToArray($metadata, "info_keywords_gemet_concepts", $gemet_concepts);
        self::addToArray($metadata, "info_keywords_adv_group", $adv_group);
        self::addToArray($metadata, "info_keywords_invekos", $invekos);
        self::addToArray($metadata, "info_keywords_topic_category", $topic_category);
        self::addToArray($metadata, "info_keywords_hvd", $hvd);
        self::addToArray($metadata, "info_keywords_search_terms", $search_terms);
    }

    private static function getKeywords(\SimpleXMLElement $node, string $lang): array
    {
        $inspire_themes = [];
        $priority_dataset = [];
        $spatial_scope = [];
        $gemet_concepts = [];
        $invekos = [];
        $hvd = [];
        $search_terms = [];

        $keywordsPath = './gmd:identificationInfo/*/gmd:descriptiveKeywords/gmd:MD_Keywords';
        $keywordsNodes = IdfHelper::getNodeList($node, $keywordsPath);
        foreach ($keywordsNodes as $keywordsNode) {
            $thesaurs_name = IdfHelper::getNodeValue($keywordsNode, './gmd:thesaurusName/gmd:CI_Citation/gmd:title/*[self::gco:CharacterString or self::gmx:Anchor]');
            $keywords = IdfHelper::getNodeValueList($keywordsNode, './gmd:keyword/*[self::gco:CharacterString or self::gmx:Anchor]');
            foreach ($keywords as $keyword) {
                $keyword = iconv('UTF-8', 'UTF-8', $keyword);
                if (empty($thesaurs_name)) {
                    $tmpValue = CodelistHelper::getCodelistEntryByData(['6400'], $keyword, $lang);
                    if (empty($tmpValue)){
                        $tmpValue = $keyword;
                    }
                    if (!in_array($tmpValue, $search_terms)) {
                        $search_terms[] = $tmpValue;
                    }
                } else {
                    if (!str_contains(strtolower($thesaurs_name), 'service')) {
                        if (str_contains(strtolower($thesaurs_name), 'concepts')) {
                            $tmpValue = CodelistHelper::getCodelistEntry(['5200'], $keyword, $lang);
                            if (empty($tmpValue)){
                                $tmpValue = $keyword;
                            }
                            if (!in_array($tmpValue, $gemet_concepts)) {
                                $gemet_concepts[] = $tmpValue;
                            }
                        } else if (str_contains(strtolower($thesaurs_name), 'priority')) {
                            $tmpValue = CodelistHelper::getCodelistEntry(['6300'], $keyword, $lang);
                            if (empty($tmpValue)){
                                $tmpValue = $keyword;
                            }
                            if (!in_array($tmpValue, $priority_dataset)) {
                                $priority_dataset[] = $tmpValue;
                            }
                        } else if (str_contains(strtolower($thesaurs_name), 'inspire')) {
                            $tmpValue = CodelistHelper::getCodelistEntry(['6100'], $keyword, $lang);
                            if (empty($tmpValue)){
                                $tmpValue = $keyword;
                            }
                            if (!in_array($tmpValue, $inspire_themes)) {
                                $inspire_themes[] = $tmpValue;
                            }
                        } else if (str_contains(strtolower($thesaurs_name), 'spatial scope')) {
                            $tmpValue = CodelistHelper::getCodelistEntry(['6360'], $keyword, $lang);
                            if (empty($tmpValue)){
                                $tmpValue = $keyword;
                            }
                            if (!in_array($tmpValue, $spatial_scope)) {
                                $spatial_scope[] = $tmpValue;
                            }
                        } else if (str_contains(strtolower($thesaurs_name), 'iacs data')) {
                            if (!in_array($keyword, $invekos)) {
                                $invekos[] = $keyword;
                            }
                        } else if (str_contains(strtolower($thesaurs_name), 'high-value')) {
                            if (!in_array($keyword, $hvd)) {
                                $hvd[] = $keyword;
                            }
                        } else if (str_contains(strtolower($thesaurs_name), 'umthes')) {
                            if (!in_array($keyword, $search_terms)) {
                                $search_terms[] = $keyword;
                            }
                        } else {
                            $tmpValue = CodelistHelper::getCodelistEntryByData(['6400'], $keyword, $lang);
                            if (empty($tmpValue)){
                                $tmpValue = $keyword;
                            }
                            if (!in_array($tmpValue, $search_terms)) {
                                $search_terms[] = $tmpValue;
                            }
                        }
                    }
                }
            }
        }

        return [$inspire_themes, $priority_dataset, $spatial_scope, $gemet_concepts, $invekos, $hvd, $search_terms];
    }

    private static function getVectors(\SimpleXMLElement $node, string $lang): array
    {
        $array = [];
        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:spatialRepresentationInfo/gmd:MD_VectorSpatialRepresentation") ?? [];
        foreach ($tmpNodes as $tmpNode) {
            $item = [];
            $map = [];
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:topologyLevel/gmd:MD_TopologyLevelCode/@codeListValue", ["528"], $lang);
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "text";
            }
            $item[] = $map;

            $map = [];
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:geometricObjects/gmd:MD_GeometricObjects/gmd:geometricObjectType/gmd:MD_GeometricObjectTypeCode/@codeListValue", ["515"], $lang);
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "text";
            }
            $item[] = $map;

            $map = [];
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:geometricObjects/gmd:MD_GeometricObjects/gmd:geometricObjectCount/gco:Integer");
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "text";
            }
            $item[] = $map;

            $array[] = $item;
        }
        return $array;
    }
    private static function getSymbolCatalogues(\SimpleXMLElement $node): array
    {
        $array = [];
        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:portrayalCatalogueInfo/gmd:MD_PortrayalCatalogueReference/gmd:portrayalCatalogueCitation/gmd:CI_Citation") ?? [];
        foreach ($tmpNodes as $tmpNode) {
            $item = [];
            $map = [];
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:title/*[self::gco:CharacterString or self::gmx:Anchor]");
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "text";
            }
            $item[] = $map;

            $map = [];
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:date/gmd:CI_Date/gmd:date/*[self::gco:Date or self::gco:DateTime]");
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "date";
            }
            $item[] = $map;

            $map = [];
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:edition/*[self::gco:CharacterString or self::gmx:Anchor]");
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "text";
            }
            $item[] = $map;

            $array[] = $item;
        }
        return $array;
    }

    private static function getFeatureCatalogues(\SimpleXMLElement $node): array
    {
        $array = [];
        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:contentInfo/gmd:MD_FeatureCatalogueDescription/gmd:featureCatalogueCitation/gmd:CI_Citation") ?? [];
        foreach ($tmpNodes as $tmpNode) {
            $item = [];
            $map = [];
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:title/*[self::gco:CharacterString or self::gmx:Anchor]");
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "text";
            }
            $item[] = $map;

            $map = [];
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:date/gmd:CI_Date/gmd:date/*[self::gco:Date or self::gco:DateTime]");
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "date";
            }
            $item[] = $map;

            $map = [];
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:edition/*[self::gco:CharacterString or self::gmx:Anchor]");
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "text";
            }
            $item[] = $map;

            $array[] = $item;
        }
        return $array;
    }

    private static function getResolutions(\SimpleXMLElement $node): array
    {
        $array = [];
        $denominators = IdfHelper::getNodeValueList($node, "./gmd:identificationInfo/*/gmd:spatialResolution/gmd:MD_Resolution/gmd:equivalentScale/gmd:MD_RepresentativeFraction/gmd:denominator/gco:Integer");
        $dpis = [];
        $meters = [];
        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:identificationInfo/*/gmd:spatialResolution/gmd:MD_Resolution/gmd:distance/gco:Distance[contains(@uom, 'dpi')]");
        foreach ($tmpNodes as $tmpNode) {
            $value = IdfHelper::getNodeValue($tmpNode, ".");
            $unit = IdfHelper::getNodeValue($tmpNode, "./@uom");
            $dpis[] = $value . " " . $unit;
        }

        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:identificationInfo/*/gmd:spatialResolution/gmd:MD_Resolution/gmd:distance/gco:Distance[contains(@uom, 'meter') or contains(@uom, 'mm') or contains(@uom, 'cm') or contains(@uom, 'm') or contains(@uom, 'km')]");
        foreach ($tmpNodes as $tmpNode) {
            $value = IdfHelper::getNodeValue($tmpNode, ".");
            $unit = IdfHelper::getNodeValue($tmpNode, "./@uom");
            if (strcasecmp($unit, "meter") == 0) {
                $unit = 'm';
            }
            $meters[] = $value . " " . $unit;
        }
        self::addToArray($array, "denominators", $denominators);
        self::addToArray($array, "dpis", $dpis);
        self::addToArray($array, "meters", $meters);
        return $array;
    }

    private static function getOperations(\SimpleXMLElement $node): array
    {
        $array = [];
        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:identificationInfo/*/srv:containsOperations/srv:SV_OperationMetadata") ?? [];
        foreach ($tmpNodes as $tmpNode) {
            $item = [];
            $map = [];
            $value = IdfHelper::getNodeValue($tmpNode, "./srv:operationName/*[self::gco:CharacterString or self::gmx:Anchor]");
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "text";
            }
            $item[] = $map;

            $map = [];
            $value = IdfHelper::getNodeValue($tmpNode, "./srv:operationDescription/*[self::gco:CharacterString or self::gmx:Anchor]");
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "text";
            }
            $item[] = $map;

            $map = [];
            $value = IdfHelper::getNodeValue($tmpNode, "./srv:invocationName/*[self::gco:CharacterString or self::gmx:Anchor]");
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "text";
            }
            $item[] = $map;

            $array[] = $item;
        }
        return $array;
    }

    private static function getConformities(\SimpleXMLElement $node, string $lang): array
    {
        $array = [];
        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:report/gmd:DQ_DomainConsistency[./gmd:result/gmd:DQ_ConformanceResult]") ?? [];
        foreach ($tmpNodes as $tmpNode) {
            $item = [];
            $map = [];
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:result/gmd:DQ_ConformanceResult/gmd:specification/gmd:CI_Citation/gmd:title/*[self::gco:CharacterString or self::gmx:Anchor]");
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "text";
            }
            $item[] = $map;

            $map = [];
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:result/gmd:DQ_ConformanceResult/gmd:specification/gmd:CI_Citation/gmd:date/gmd:CI_Date/gmd:date/gco:Date");
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "date";
            }
            $item[] = $map;

            $map = [];
            $tmpSubNode = IdfHelper::getNode($tmpNode, "./gmd:result/gmd:DQ_ConformanceResult/gmd:pass");
            if (!is_null($tmpSubNode)) {
                $value = IdfHelper::getNodeValue($tmpSubNode, "./gco:Boolean");
                if (is_null($value)) {
                    $value = "";
                }
                if (!is_null($value)) {
                    $map["value"] = $value;
                    $map["type"] = "symbol";
                    $title = CodelistHelper::getCodelistEntry(["6000"], "3", $lang);
                    if (strcmp($value, "true") == 0) {
                        $title = CodelistHelper::getCodelistEntry(["6000"], "1", $lang);
                    } elseif (strcmp($value, "false") == 0) {
                        $title = CodelistHelper::getCodelistEntry(["6000"], "2", $lang);
                    }
                    $map["title"] = $title;
                }
            }
            $item[] = $map;

            $map = [];
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:result/gmd:DQ_ConformanceResult/gmd:explanation/*[self::gco:CharacterString or self::gmx:Anchor]");
            if ($value && strcasecmp($value, "see the referenced specification") != 0) {
                $map["value"] = $value;
                $map["type"] = "text";
            }
            $item[] = $map;
            $array[] = $item;
        }
        return $array;
    }

    private static function getDataformats(\SimpleXMLElement $node): array
    {
        $array = [];
        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:distributionInfo/gmd:MD_Distribution/gmd:distributionFormat/gmd:MD_Format") ?? [];
        foreach ($tmpNodes as $tmpNode) {
            $item = [];
            $name = IdfHelper::getNodeValue($tmpNode, "./gmd:name/*[self::gco:CharacterString or self::gmx:Anchor]");
            $version = IdfHelper::getNodeValue($tmpNode, "./gmd:version/*[self::gco:CharacterString or self::gmx:Anchor]");
            $fileDecompression = IdfHelper::getNodeValue($tmpNode, "./gmd:fileDecompressionTechnique/*[self::gco:CharacterString or self::gmx:Anchor]");
            $specification = IdfHelper::getNodeValue($tmpNode, "./gmd:specification/*[self::gco:CharacterString or self::gmx:Anchor]");
            if ($name != "Geographic Markup Language (GML)" && $version != "unknown" && ($fileDecompression || $specification)) {
                $map = [];
                if ($name) {
                    $map["value"] = $name;
                    $map["type"] = "text";
                }
                $item[] = $map;

                $map = [];
                if ($version) {
                    $map["value"] = $version;
                    $map["type"] = "text";
                }
                $item[] = $map;

                $map = [];

                if ($fileDecompression) {
                    $map["value"] = $fileDecompression;
                    $map["type"] = "text";
                }
                $item[] = $map;

                $map = [];

                if ($specification) {
                    $map["value"] = $specification;
                    $map["type"] = "text";
                }
                $item[] = $map;
            }
            if ($item) {
                $array[] = $item;
            }
        }
        return $array;
    }

    private static function getMedias(\SimpleXMLElement $node): array
    {
        $array = [];
        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions[./gmd:offLine]") ?? [];
        foreach ($tmpNodes as $tmpNode) {
            $item = [];
            $map = [];
            $unit = "MB";

            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:unitsOfDistribution/*[self::gco:CharacterString or self::gmx:Anchor]");
            if ($value) {
                $unit = $value;
            }

            $value = IdfHelper::getNode($tmpNode, "./gmd:offLine/gmd:MD_Medium/gmd:name/gmd:MD_MediumNameCode[./@codeListValue]");
            if ($value) {
                $map["value"] = IdfHelper::getNodeValue($value, "./@codeListValue");
                $map["type"] = "text";
            }
            $item[] = $map;

            $map = [];
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:transferSize/gco:Real");
            if ($value) {
                $map["value"] = $value . " " . $unit;
                $map["type"] = "text";
            }
            $item[] = $map;

            $map = [];
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:offLine/gmd:MD_Medium/gmd:mediumNote/*[self::gco:CharacterString or self::gmx:Anchor]");
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "text";
            }
            $item[] = $map;
            $array[] = $item;
        }
        return $array;
    }

    private static function getDataQualityRefs(\SimpleXMLElement $node, array &$metadata): void
    {
        $completenessOmission = self::getReport($node, "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:report/gmd:DQ_CompletenessOmission", "completeness omission (rec_grade)", "Rate of missing items");
        $accuracyVertical = self::getReport($node, "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:report/gmd:DQ_AbsoluteExternalPositionalAccuracy", "vertical", "Mean value of positional uncertainties (1D)");
        $accuracyGeographic = self::getReport($node, "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:report/gmd:DQ_AbsoluteExternalPositionalAccuracy", "geographic", "Mean value of positional uncertainties (2D)");
        $completenessCommission = self::getReportList($node, "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:report/gmd:DQ_CompletenessCommission[./gmd:nameOfMeasure]");
        $conceptualConsistency = self::getReportList($node, "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:report/gmd:DQ_ConceptualConsistency[./gmd:nameOfMeasure]");
        $domainConsistency = self::getReportList($node, "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:report/gmd:DQ_DomainConsistency[./gmd:nameOfMeasure]");
        $formatConsistency = self::getReportList($node, "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:report/gmd:DQ_FormatConsistency[./gmd:nameOfMeasure]");
        $topologicalConsistency = self::getReportList($node, "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:report/gmd:DQ_TopologicalConsistency[./gmd:nameOfMeasure]");
        $temporalConsistency = self::getReportList($node, "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:report/gmd:DQ_TemporalConsistency[./gmd:nameOfMeasure]");
        $thematicClassificationCorrectness = self::getReportList($node, "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:report/gmd:DQ_ThematicClassificationCorrectness[./gmd:nameOfMeasure]");
        $nonQuantitativeAttributeAccuracy = self::getReportList($node, "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:report/gmd:DQ_NonQuantitativeAttributeAccuracy[./gmd:nameOfMeasure]");
        $quantitativeAttributeAccuracy = self::getReportList($node, "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:report/gmd:DQ_QuantitativeAttributeAccuracy[./gmd:nameOfMeasure]");
        $relativeInternalPositionalAccuracy = self::getReportList($node, "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:report/gmd:DQ_RelativeInternalPositionalAccuracy[./gmd:nameOfMeasure]");

        self::addToArray($metadata, "data_quality_completenessOmission", $completenessOmission);
        self::addToArray($metadata, "data_quality_accuracyVertical", $accuracyVertical);
        self::addToArray($metadata, "data_quality_accuracyGeographic", $accuracyGeographic);
        self::addToArray($metadata, "data_quality_completenessCommission", $completenessCommission);
        self::addToArray($metadata, "data_quality_conceptualConsistency", $conceptualConsistency);
        self::addToArray($metadata, "data_quality_domainConsistency", $domainConsistency);
        self::addToArray($metadata, "data_quality_formatConsistency", $formatConsistency);
        self::addToArray($metadata, "data_quality_topologicalConsistency", $topologicalConsistency);
        self::addToArray($metadata, "data_quality_temporalConsistency", $temporalConsistency);
        self::addToArray($metadata, "data_quality_thematicClassificationCorrectness", $thematicClassificationCorrectness);
        self::addToArray($metadata, "data_quality_nonQuantitativeAttributeAccuracy", $nonQuantitativeAttributeAccuracy);
        self::addToArray($metadata, "data_quality_quantitativeAttributeAccuracy", $quantitativeAttributeAccuracy);
        self::addToArray($metadata, "data_quality_relativeInternalPositionalAccuracy", $relativeInternalPositionalAccuracy);
    }

    private static function getReport(\SimpleXMLElement $node, string $xpath, string $dependedDescription, string $dependedName): null|string
    {
        $value = null;
        $tmpNodes = IdfHelper::getNodeList($node, $xpath . "[(./gmd:measureDescription/*[self::gco:CharacterString or self::gmx:Anchor]='".$dependedDescription."')][(./gmd:nameOfMeasure/*[self::gco:CharacterString or self::gmx:Anchor]='".$dependedName."')]") ?? [];
        foreach ($tmpNodes as $tmpNode) {
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:result/gmd:DQ_QuantitativeResult/gmd:value/gco:Record");
            $symbol = IdfHelper::getNodeValue($tmpNode, "./gmd:result/gmd:DQ_QuantitativeResult/gmd:valueUnit/gml:UnitDefinition/gml:catalogSymbol");
            if(!is_null($value) && !is_null($symbol)) {
                $value = $value . " " . $symbol;
            }
        }
        return $value;
    }

    private static function getReportList(\SimpleXMLElement $node, string $xpath): array
    {
        $array = [];
        $tmpNodes = IdfHelper::getNodeList($node, $xpath) ?? [];
        foreach ($tmpNodes as $tmpNode) {
            $name = IdfHelper::getNodeValue($tmpNode, "./gmd:nameOfMeasure/*[self::gco:CharacterString or self::gmx:Anchor]");
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:result/gmd:DQ_QuantitativeResult/gmd:value/gco:Record");
            $description = IdfHelper::getNodeValue($tmpNode, "./gmd:measureDescription/*[self::gco:CharacterString or self::gmx:Anchor]");
            $item = [];

            $map = [];
            if ($name) {
                $map["value"] = $name;
                $map["type"] = "text";
            }
            $item[] = $map;

            $map = [];
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "text";
            }
            $item[] = $map;

            $map = [];
            if ($description) {
                $map["value"] = $description;
                $map["type"] = "text";
            }
            $item[] = $map;
            $array[] = $item;
        }
        return $array;
    }

    private static function getMetaInfoRefs(\SimpleXMLElement $node, string $uuid, null|string $dataSourceName, array $providers, array &$metadata, string $lang): void
    {
        $mod_time = IdfHelper::getNodeValue($node, "./gmd:dateStamp/*[self::gco:Date or self::gco:DateTime or .]");
        $langCode = IdfHelper::getNodeValue($node, "./gmd:language/gmd:LanguageCode/@codeListValue");
        $hierarchy_level = IdfHelper::getNodeValue($node, "./gmd:hierarchyLevel/gmd:MD_ScopeCode/@codeListValue", ["525"], $lang);
        $contact_meta = [];
        self::addToArray($contact_meta,"mail", IdfHelper::getNodeValueList($node, "./gmd:contact/*/gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:electronicMailAddress/*[self::gco:CharacterString or self::gmx:Anchor]"));
        self::addToArray($contact_meta,"role", IdfHelper::getNodeValue($node, "./gmd:contact/*/gmd:role/gmd:CI_RoleCode/@codeListValue", ["505"], $lang));

        self::addToArray($metadata, "meta_info_uuid", $uuid);
        self::addToArray($metadata, "meta_info_mod_time", $mod_time);
        self::addToArray($metadata, "meta_info_lang", LanguageHelper::getNameFromIso639_2($langCode, $lang));
        self::addToArray($metadata, "meta_info_hierarchy_level", $hierarchy_level);
        self::addToArray($metadata, "meta_info_contact_meta", $contact_meta);
        self::addToArray($metadata, "meta_info_plug_data_source_name", $dataSourceName);
        self::addToArray($metadata, "meta_info_plug_providers", $providers);
    }

    private static function getMapUrl(\SimpleXMLElement $node, string $type): null|string
    {
        $value = null;
        if ($type == '1') {
            $value = IdfHelper::getNodeValue($node, './idf:mapUrl');
        }
        if (!$value) {
            $crossRefNodes = IdfHelper::getNodeList($node, './idf:crossReference');
            foreach ($crossRefNodes as $crossRefNode) {
                $mapUrl =  IdfHelper::getNodeValue($crossRefNode, "./idf:mapUrl");
                if ($mapUrl) {
                    $value = $mapUrl;
                } else {
                    $serviceUrl =  IdfHelper::getNodeValue($crossRefNode, "./idf:serviceUrl");
                    $serviceType =  IdfHelper::getNodeValue($crossRefNode, "./idf:serviceType");
                    $serviceVersion =  IdfHelper::getNodeValue($crossRefNode, "./idf:serviceVersion");
                    if (!empty($serviceUrl)) {
                        $value = CapabilitiesHelper::getMapUrl($serviceUrl, $serviceVersion, $serviceType);
                    }
                }
                if ($value) {
                    break;
                }
            }
        }
        if (!$value) {
            $transOptionNodes = IdfHelper::getNodeList($node, './gmd:distributionInfo/*/gmd:transferOptions');
            foreach ($transOptionNodes as $transOptionNode) {
                $url = IdfHelper::getNodeValue($transOptionNode, './gmd:MD_DigitalTransferOptions/gmd:onLine/*/gmd:linkage/gmd:URL');
                if ($url) {
                    $serviceType = IdfHelper::getNodeValue($transOptionNode, "./gmd:MD_DigitalTransferOptions/gmd:onLine/*/gmd:function/gmd:CI_OnLineFunctionCode");
                    if (($serviceType != null && (strtolower(trim($serviceType)) == 'view' || strtolower(trim($serviceType)) == 'wms') || strtolower(trim($serviceType)) == 'wmts') &&
                        ((str_contains(strtolower($url), 'request=getcapabilities') && (str_contains(strtolower($url), 'service=wms')) || str_contains(strtolower($url), 'service=wmts')) ||
                        str_contains(strtolower($url), 'wmtscapabilities.xml'))
                    ) {
                        $value = CapabilitiesHelper::getMapUrl($url);
                    }
                }
            }
        }
        return $value;
    }

    private static function addToArray(array &$array, string $id, mixed $value): void
    {
        if (!empty($value)) {
            $array[$id] = $value;
        }
    }
}
