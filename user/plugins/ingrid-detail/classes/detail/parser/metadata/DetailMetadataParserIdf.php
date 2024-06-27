<?php

namespace Grav\Plugin;

class DetailMetadataParserIdf
{

    public static function parse($node, $uuid, $dataSourceName, $provider){
        echo "<script>console.log('InGrid Detail parse metadata with " . $uuid . "');</script>";

        $metadata = array();
        $metadata["uuid"] = $uuid;
        $metadata["parent_uuid"] = IdfHelper::getNodeValue($node, "./gmd:parentIdentifier/*[self::gco:CharacterString or self::gmx:Anchor]");
        $metadata["type"] = self::getType($node);
        $metadata["title"] = IdfHelper::getNodeValue($node, "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:title/*[self::gco:CharacterString or self::gmx:Anchor]");
        $metadata["altTitle"] = IdfHelper::getNodeValue($node, "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:alternateTitle/*[self::gco:CharacterString or self::gmx:Anchor]");
        $metadata["summary"] = IdfHelper::getNodeValue($node, "./idf:abstract/*[self::gco:CharacterString or self::gmx:Anchor] | ./gmd:abstract/*[self::gco:CharacterString or self::gmx:Anchor]");
        $metadata["accessConstraint"] = IdfHelper::getNodeValue($node, "./idf:hasAccessConstraint");
        self::getPreviews($node, $metadata);
        self::getTimeRefs($node, $metadata);
        self::getMapRefs($node, $metadata);
        self::getLinkRefs($node, $metadata["type"], $metadata);
        self::getUseRefs($node, $metadata);
        self::getContactRefs($node, $metadata);
        self::getInfoRefs($node, $metadata["type"], $metadata);
        self::getDataQualityRefs($node, $metadata);
        self::getMetaInfoRefs($node, $uuid, $dataSourceName, $provider, $metadata);
        //var_dump($metadata);
        return $metadata;
    }

    public static function getPreviews($node, &$metadata)
    {
        $array = array();
        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:identificationInfo/*/gmd:graphicOverview/gmd:MD_BrowseGraphic");
        foreach ($tmpNodes as $tmpNode) {
            $map = array ();
            $url = IdfHelper::getNodeValue($tmpNode, "./gmd:fileName/*[self::gco:CharacterString or self::gmx:Anchor]");
            $descr = IdfHelper::getNodeValue($tmpNode, "./gmd:fileDescription/*[self::gco:CharacterString or self::gmx:Anchor]");
            $map["url"] = $url;
            $map["descr"] = $descr;
            array_push($array, $map);
        }
        $metadata["previews"] = $array;
    }

    public static function getType($node)
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

    public static function getTimeRefs($node, &$metadata)
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
            $metadata["time_status"] = $tmpValue;
        }

        ## Periodizität
        $tmpValue = IdfHelper::getNodeValue($node, "./*/*/gmd:resourceMaintenance/gmd:MD_MaintenanceInformation/gmd:maintenanceAndUpdateFrequency/gmd:MD_MaintenanceFrequencyCode/@codeListValue");
        if ($tmpValue) {
            $metadata["time_period"] = $tmpValue;
        }

        ## Intervall der Erhebung
        $tmpValue = IdfHelper::getNodeValue($node, "./*/*/gmd:resourceMaintenance/gmd:MD_MaintenanceInformation/gmd:userDefinedMaintenanceFrequency/gts:TM_PeriodDuration");
        if ($tmpValue) {
            $metadata["time_interval"] = $tmpValue;
        }

        ## Erläuterung zum Zeitbezug
        $tmpValue = IdfHelper::getNodeValue($node, "./*/*/gmd:resourceMaintenance/gmd:MD_MaintenanceInformation/gmd:maintenanceNote/*[self::gco:CharacterString or self::gmx:Anchor]");
        if ($tmpValue) {
            $metadata["time_descr"] = $tmpValue;
        }

        ## Zeitbezug der Ressource
        $tmpValue = IdfHelper::getNode($node, "./*/*/gmd:citation/gmd:CI_Citation/gmd:date/gmd:CI_Date[./gmd:date]");
        if ($tmpValue) {
            $tmpValue = IdfHelper::getNodeValue($node, "./*/*/gmd:citation/gmd:CI_Citation/gmd:date/gmd:CI_Date[gmd:dateType/gmd:CI_DateTypeCode/@codeListValue = 'creation']/gmd:date/gco:DateTime");
            if ($tmpValue) {
                $metadata["time_creation"] = $tmpValue;
            }
            $tmpValue = IdfHelper::getNodeValue($node, "./*/*/gmd:citation/gmd:CI_Citation/gmd:date/gmd:CI_Date[gmd:dateType/gmd:CI_DateTypeCode/@codeListValue = 'publication']/gmd:date/gco:DateTime");
            if ($tmpValue) {
                $metadata["time_publication"] = $tmpValue;
            }
            $tmpValue = IdfHelper::getNodeValue($node, "./*/*/gmd:citation/gmd:CI_Citation/gmd:date/gmd:CI_Date[gmd:dateType/gmd:CI_DateTypeCode/@codeListValue = 'revision']/gmd:date/gco:DateTime");
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

    public static function getMapRefs($node, &$metadata)
    {
        self::getBBoxes($node, $metadata);
        self::getGeographicElements($node, $metadata);
        $regionKey = IdfHelper::getNode($node, "./idf:regionKey");
        self::getAreaHeight($node, $metadata);
        $loc_descr = IdfHelper::getNode($node, "./gmd:identificationInfo/*/*/gmd:EX_Extent/gmd:description/*[self::gco:CharacterString or self::gmx:Anchor]");
        $polygon_wkt = IdfHelper::getNode($node, "./gmd:identificationInfo/*/*/gmd:EX_Extent/gmd:geographicElement/gmd:EX_BoundingPolygon/gmd:polygon");
        $referencesystem_id = IdfHelper::getNodeValueList($node, "./gmd:referenceSystemInfo/gmd:MD_ReferenceSystem/gmd:referenceSystemIdentifier/gmd:RS_Identifier/gmd:code/*[self::gco:CharacterString or self::gmx:Anchor]");
        $metadata["map_regionKey"] = $regionKey;
        $metadata["map_loc_descr"] = $loc_descr;
        $metadata["map_polygon_wkt"] = $polygon_wkt;
        $metadata["map_referencesystem_id"] = $referencesystem_id;
    }

    public static function getBBoxes($node, &$metadata)
    {
        $array = array();
        $geographicIdentifiers = [];
        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:identificationInfo/*/*/gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicDescription/gmd:geographicIdentifier/gmd:MD_Identifier/gmd:code/*[self::gco:CharacterString or self::gmx:Anchor]");
        foreach ($tmpNodes as $tmpNode) {
            $value = (string) IdfHelper::getNodeValue($tmpNode, ".");
            array_push($geographicIdentifiers, $value);
        }

        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:identificationInfo/*/*/gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicBoundingBox");
        $count = 0;
        foreach ($tmpNodes as $tmpNode) {
            $item = [];

            $value = $geographicIdentifiers[$count];
            $map = array ();
            $map["title"] = $value;

            $map["westBoundLongitude"] = (float)IdfHelper::getNodeValue($tmpNode, "./gmd:westBoundLongitude/gco:Decimal");
            $map["southBoundLatitude"] = (float)IdfHelper::getNodeValue($tmpNode, "./gmd:southBoundLatitude/gco:Decimal");
            $map["eastBoundLongitude"] = (float)IdfHelper::getNodeValue($tmpNode, "./gmd:eastBoundLongitude/gco:Decimal");
            $map["northBoundLatitude"] = (float)IdfHelper::getNodeValue($tmpNode, "./gmd:northBoundLatitude/gco:Decimal");

            array_push($array, $map);
            $count++;
        }
        $metadata["map_bboxes"] = $array;
    }

    public static function getGeographicElements($node, &$metadata)
    {
        $array = array();
        $geographicIdentifiers = [];
        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:identificationInfo/*/*/gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicDescription/gmd:geographicIdentifier/gmd:MD_Identifier/gmd:code/*[self::gco:CharacterString or self::gmx:Anchor]");
        foreach ($tmpNodes as $tmpNode) {
            $value = (string) IdfHelper::getNodeValue($tmpNode, ".");
            array_push($geographicIdentifiers, $value);
        }

        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:identificationInfo/*/*/gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicBoundingBox");
        $count = 0;
        foreach ($tmpNodes as $tmpNode) {
            $item = [];

            $value = $geographicIdentifiers[$count];
            $map = array ();
            $map["value"] = $value;
            $map["type"] = "text";
            array_push($item, $map);

            $westBoundLongitude = (float)IdfHelper::getNodeValue($tmpNode, "./gmd:westBoundLongitude/gco:Decimal");
            $southBoundLatitude = (float)IdfHelper::getNodeValue($tmpNode, "./gmd:southBoundLatitude/gco:Decimal");
            $eastBoundLongitude = (float)IdfHelper::getNodeValue($tmpNode, "./gmd:eastBoundLongitude/gco:Decimal");
            $northBoundLatitude = (float)IdfHelper::getNodeValue($tmpNode, "./gmd:northBoundLatitude/gco:Decimal");

            $map = array ();
            if ($westBoundLongitude && $southBoundLatitude) {
                $map["value"] = round($westBoundLongitude, 3) . "°/" . round($southBoundLatitude, 3) . "°";
                $map["type"] = "text";
            } else {
                $map["value"] = "";
                $map["type"] = "text";
            }
            array_push($item, $map);

            $map = array ();
            if ($eastBoundLongitude && $northBoundLatitude) {
                $map["value"] = round($eastBoundLongitude, 3) . "°/" . round($northBoundLatitude, 3) . "°";
                $map["type"] = "text";
            } else {
                $map["value"] = "";
                $map["type"] = "text";
            }
            array_push($item, $map);

            array_push($array, $item);
            $count++;
        }
        $metadata["map_geographicElement"] = $array;
    }

    public static function getAreaHeight($node, &$metadata)
    {
        $array = array();
        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:identificationInfo/*/*/gmd:EX_Extent/gmd:verticalElement/gmd:EX_VerticalExtent");
        foreach ($tmpNodes as $tmpNode) {
            $item = [];

            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:minimumValue/gco:Real");
            $map = array ();
            $map["value"] = $value;
            $map["type"] = "text";
            array_push($item, $map);

            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:maximumValue/gco:Real");
            $map = array ();
            $map["value"] = $value;
            $map["type"] = "text";
            array_push($item, $map);

            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:verticalCRS/gml:VerticalCRS/gml:verticalCS/gml:VerticalCS/gml:axis/gml:CoordinateSystemAxis/@uom");
            $map = array ();
            $map["value"] = $value;
            $map["type"] = "text";
            array_push($item, $map);

            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:verticalCRS/gml:VerticalCRS/gml:verticalDatum/gml:VerticalDatum/gml:name");
            if(is_null($value) || empty($value)) {
                $value = IdfHelper::getNodeValue($tmpNode, "./gmd:verticalCRS/gml:VerticalCRS/gml:verticalDatum/gml:VerticalDatum/gml:identifier");
            }
            if(is_null($value) || empty($value)) {
                $value = IdfHelper::getNodeValue($tmpNode, "./gmd:verticalCRS/gml:VerticalCRS/gml:name");
            }
            $map = array ();
            $map["value"] = $value;
            $map["type"] = "text";
            array_push($item, $map);

            array_push($array, $item);
        }
        $metadata["map_areaHeight"] = $array;
    }

    public static function getLinkRefs($node, $type, &$metadata)
    {
        $array = array();

        $xpathExpression = "./idf:crossReference[not(@uuid=preceding::idf:crossReference/@uuid)]";
        $tmpNodes = IdfHelper::getNodeList($node, $xpathExpression);
        foreach ($tmpNodes as $tmpNode) {
            $uuid = IdfHelper::getNodeValue($tmpNode, "./@uuid");
            $title = IdfHelper::getNodeValue($tmpNode, "./idf:objectName");
            $description = IdfHelper::getNodeValue($tmpNode, "./idf:description");
            $metaClass = IdfHelper::getNodeValue($tmpNode, "./idf:objectType");
            $previews = IdfHelper::getNodeValueList($tmpNode, "./idf:graphicOverview");
            $extMapUrl = IdfHelper::getNodeList($tmpNode, "./idf:extMapUrl");
            $mapUrl = IdfHelper::getNodeList($tmpNode, "./idf:mapUrl");
            $item = array (
                "uuid" => $uuid,
                "title" => $title,
                "description" => $description,
                "metaClass" => $metaClass,
                "previews" => $previews,
                "extMapUrl" => $extMapUrl,
                "mapUrl" => $mapUrl,
                "kind" => "object",
            );
            array_push($array, $item);
        }

        $xpathExpression = "./gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine[not(./*/idf:attachedToField[@entry-id='9990']) and not(./*/gmd:function/*/@codeListValue='download') and (./*/gmd:applicationProfile/*[self::gco:CharacterString or self::gmx:Anchor]='coupled')]";
        if ($type == "1")
            $xpathExpression = "./gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine[not(./*/idf:attachedToField[@entry-id='9990']) and not(./*/gmd:function/*/@codeListValue='download') and not(./*/*/gmd:URL[contains(translate(text(),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'), 'getcap')] and (./*/gmd:applicationProfile/*[self::gco:CharacterString or self::gmx:Anchor]='coupled'))]";
        elseif($type == "6")
            $xpathExpression = "./gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine[not(./*/idf:attachedToField[@entry-id='9990']) and not(./*/gmd:function/*/@codeListValue='download') and (./*/gmd:applicationProfile/*[self::gco:CharacterString or self::gmx:Anchor]='coupled')]";

        $tmpNodes = IdfHelper::getNodeList($node, $xpathExpression);
        foreach ($tmpNodes as $tmpNode) {
            $title = IdfHelper::getNodeValue($tmpNode, "./*/gmd:name/*[self::gco:CharacterString or self::gmx:Anchor]");
            $description = IdfHelper::getNodeValue($tmpNode, "./*/gmd:description/*[self::gco:CharacterString or self::gmx:Anchor]");
            $cswUrl = IdfHelper::getNodeValue($tmpNode, "./*/gmd:linkage/gmd:URL");
            $metaClass = "1";
            $item = array (
                "title" => $title,
                "description" => $description,
                "metaClass" => $metaClass,
                "cswUrl" => $cswUrl,
                "kind" => "object",
            );
            array_push($array, $item);
        }

        $xpathExpression = "./gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions[not(./gmd:MD_DigitalTransferOptions/gmd:onLine/*/idf:attachedToField[@entry-id='9990']) and not(./gmd:MD_DigitalTransferOptions/gmd:onLine/*/gmd:function/*/@codeListValue='download')]/gmd:MD_DigitalTransferOptions/gmd:onLine";
        $tmpNodes = IdfHelper::getNodeList($node, $xpathExpression);
        foreach ($tmpNodes as $tmpNode) {
            $url = IdfHelper::getNodeValue($tmpNode, "./*/gmd:linkage/gmd:URL");
            $title = IdfHelper::getNodeValue($tmpNode, "./*/gmd:name/*[self::gco:CharacterString or self::gmx:Anchor]");
            $description = IdfHelper::getNodeValue($tmpNode, "./*/gmd:description/*[self::gco:CharacterString or self::gmx:Anchor]");
            $attachedToField = IdfHelper::getNodeValue($tmpNode, "./*/idf:attachedToField");
            $applicationProfile = IdfHelper::getNodeValue($tmpNode, "./*/gmd:applicationProfile/*[self::gco:CharacterString or self::gmx:Anchor]");
            $size = IdfHelper::getNodeValue($tmpNode, "./*/gmd:MD_DigitalTransferOptions/gmd:transferSize");
            $item = array (
                "url" => $url,
                "title" => $title,
                "description" => $description,
                "attachedToField" => $attachedToField,
                "applicationProfile" => $applicationProfile,
                "linkInfo" => $size,
                "kind" => "other",
            );
            array_push($array, $item);
        }

        $xpathExpression = "./gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine[./*/idf:attachedToField[@entry-id='9990'] or ./*/gmd:function/*/@codeListValue='download']";
        $tmpNodes = IdfHelper::getNodeList($node, $xpathExpression);
        foreach ($tmpNodes as $tmpNode) {
            $url = IdfHelper::getNodeValue($tmpNode, "./*/gmd:linkage/gmd:URL");
            $title = IdfHelper::getNodeValue($tmpNode, "./*/gmd:name/*[self::gco:CharacterString or self::gmx:Anchor]");
            $description = IdfHelper::getNodeValue($tmpNode, "./*/gmd:description/*[self::gco:CharacterString or self::gmx:Anchor]");
            $attachedToField = IdfHelper::getNodeValue($tmpNode, "./*/idf:attachedToField");
            $applicationProfile = IdfHelper::getNodeValue($tmpNode, "./*/gmd:applicationProfile/*[self::gco:CharacterString or self::gmx:Anchor]");
            $size = IdfHelper::getNodeValue($tmpNode, "./*/gmd:MD_DigitalTransferOptions/gmd:transferSize");
            $item = array (
                "url" => $url,
                "title" => $title,
                "description" => $description,
                "attachedToField" => $attachedToField,
                "applicationProfile" => $applicationProfile,
                "linkInfo" => $size,
                "kind" => "download",
            );
            array_push($array, $item);
        }

        $xpathExpression = "./idf:superiorReference";
        $tmpNodes = IdfHelper::getNodeList($node, $xpathExpression);
        foreach ($tmpNodes as $tmpNode) {
            $uuid = IdfHelper::getNodeValue($tmpNode, "./@uuid");
            $title = IdfHelper::getNodeValue($tmpNode, "./idf:objectName");
            $description = IdfHelper::getNodeValue($tmpNode, "./idf:description");
            $metaClass = IdfHelper::getNodeValue($tmpNode, "./idf:objectType");
            $previews = IdfHelper::getNodeValueList($tmpNode, "./idf:graphicOverview");
            $extMapUrl = IdfHelper::getNodeList($tmpNode, "./idf:extMapUrl");
            $mapUrl = IdfHelper::getNodeList($tmpNode, "./idf:mapUrl");
            $item = array (
                "uuid" => $uuid,
                "title" => $title,
                "description" => $description,
                "metaClass" => $metaClass,
                "previews" => $previews,
                "extMapUrl" => $extMapUrl,
                "mapUrl" => $mapUrl,
                "kind" => "superior",
            );
            array_push($array, $item);
        }

        $xpathExpression = "./idf:subordinatedReference";
        $tmpNodes = IdfHelper::getNodeList($node, $xpathExpression);
        foreach ($tmpNodes as $tmpNode) {
            $uuid = IdfHelper::getNodeValue($tmpNode, "./@uuid");
            $title = IdfHelper::getNodeValue($tmpNode, "./idf:objectName");
            $description = IdfHelper::getNodeValue($tmpNode, "./idf:description");
            $metaClass = IdfHelper::getNodeValue($tmpNode, "./idf:objectType");
            $previews = IdfHelper::getNodeList($tmpNode, "./idf:graphicOverview");
            $extMapUrl = IdfHelper::getNodeList($tmpNode, "./idf:extMapUrl");
            $mapUrl = IdfHelper::getNodeList($tmpNode, "./idf:mapUrl");
            $item = array (
                "uuid" => $uuid,
                "title" => $title,
                "description" => $description,
                "metaClass" => $metaClass,
                "previews" => $previews,
                "extMapUrl" => $extMapUrl,
                "mapUrl" => $mapUrl,
                "kind" => "subordinated",
            );
            array_push($array, $item);
        }

        if ($type == 3) {
            $xpathExpression = "./gmd:identificationInfo/*/srv:containsOperations/srv:SV_OperationMetadata[./srv:operationName/*[self::gco:CharacterString or self::gmx:Anchor][contains(translate(text(),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'), '=getcap')]]/srv:connectPoint";
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
                array_push($array, $item);
            }
        } else if ($type == 6) {
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
                array_push($array, $item);
            }
        }
        $metadata["links"] = $array;
    }

    public static function getUseRefs($node, &$metadata)
    {
        $useLimitations = IdfHelper::getNodeValueList($node, "./gmd:identificationInfo/*/gmd:resourceConstraints/gmd:MD_LegalConstraints/gmd:useLimitation/*[self::gco:CharacterString or self::gmx:Anchor]");
        $useConstraints = IdfHelper::getNodeValueList($node, "./gmd:identificationInfo/*/gmd:resourceConstraints/gmd:MD_LegalConstraints[gmd:useConstraints/gmd:MD_RestrictionCode/@codeListValue='otherRestrictions']/gmd:otherConstraints/*[self::gco:CharacterString or self::gmx:Anchor][starts-with(text(),'{')]");
        if (empty($useConstraints)) {
            $useConstraints = IdfHelper::getNodeValueList($node, "./gmd:identificationInfo/*/gmd:resourceConstraints/gmd:MD_LegalConstraints[gmd:useConstraints/gmd:MD_RestrictionCode/@codeListValue='otherRestrictions']/gmd:otherConstraints/*[self::gco:CharacterString or self::gmx:Anchor][not(starts-with(text(),'{'))]");
        }
        $accessConstraints = IdfHelper::getNodeValueList($node, "./gmd:identificationInfo/*/gmd:resourceConstraints/gmd:MD_LegalConstraints[./gmd:accessConstraints/gmd:MD_RestrictionCode[contains(@codeListValue, 'otherRestrictions')]]/gmd:otherConstraints/*[self::gco:CharacterString or self::gmx:Anchor]");

        $metadata["use_useLimitations"] = $useLimitations;
        $metadata["use_accessConstraints"] = $accessConstraints;
        $metadata["use_useConstraints"] = $useConstraints;
    }

    public static function getContactRefs($node, &$metadata)
    {
        $array = array();
        $nodes = IdfHelper::getNodeList($node, "./gmd:identificationInfo/*/gmd:pointOfContact/*");

        foreach ($nodes as $tmpNode) {
            $uuid = "";
            $type = "";
            $role = "";
            $roleNode = IdfHelper::getNode($tmpNode, "./gmd:role/gmd:CI_RoleCode");
            if ($roleNode) {
                $role = IdfHelper::getNodeValue($roleNode, "./@codeListValue");
            }
            $addresses = [];
            $tmpAddresses = IdfHelper::getNodeList($tmpNode, "./idf:hierarchyParty");
            if(is_null($tmpAddresses)) {
                $tmpAddresses = IdfHelper::getNodeList($tmpNode, "./");
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
                $item = array (
                    "uuid" => $uuid,
                    "type" => $type,
                    "title" => $title,
                );
                array_push($addresses, $item);
            }

            $streets = [];
            $tmpStreets = IdfHelper::getNodeList($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:deliveryPoint/*[self::gco:CharacterString or self::gmx:Anchor]/text()") ?? [];
            foreach ($tmpStreets as $tmpStreet) {
                $tmpArray = explode(',', $tmpStreet);
                foreach ($tmpArray as $tmp) {
                    if (str_starts_with($tmpStreet, 'Postbox ')) {
                        $tmp = str_replace('Postbox ', 'Postfach ', $tmp);
                        array_push($streets, $tmp);
                    } else {
                        array_push($streets, $tmp);
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
                "title" => $title,
                "addresses" => $addresses,
                "streets" => $streets,
                "postcode" => $postcode,
                "city" => $city,
                "country" => $country,
                "mail" => $mail,
                "phone" => $phone,
                "facsimile" => $facsimile,
                "url" => $url,
                "service_time" => $service_time,
            );
            array_push($array, $item);
        }
        $metadata["contacts"] = $array;
    }

    public static function getInfoRefs($node, $type, &$metadata)
    {

        $array = array();
        $xpathExpression = "./gmd:identificationInfo/*/gmd:descriptiveKeywords/gmd:MD_Keywords[gmd:thesaurusName/gmd:CI_Citation/gmd:title/*[self::gco:CharacterString or self::gmx:Anchor][contains(text(), 'Service')]]/gmd:keyword/*[self::gco:CharacterString or self::gmx:Anchor]";
        $array["classifications"] = IdfHelper::getNodeValueList($node, $xpathExpression);
        $xpathExpression = "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:lineage/gmd:LI_Lineage/gmd:statement/*[self::gco:CharacterString or self::gmx:Anchor]";
        $array["lineageStatement"] = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:identificationInfo/*/srv:serviceType/gco:LocalName";
        $array["serviceType"] = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:identificationInfo/*/srv:serviceTypeVersion/*[self::gco:CharacterString or self::gmx:Anchor]";
        $array["serviceTypeVersions"] = IdfHelper::getNodeValueList($node, $xpathExpression);
        $array["resolutions"] = self::getResolutions($node);
        $xpathExpression = "./gmd:identificationInfo/*/gmd:environmentDescription/*[self::gco:CharacterString or self::gmx:Anchor]";
        $array["environmentDescription"] = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:lineage/gmd:LI_Lineage/gmd:processStep/gmd:LI_ProcessStep/gmd:description/*[self::gco:CharacterString or self::gmx:Anchor]";
        $array["processStepDescription"] = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:lineage/gmd:LI_Lineage/gmd:source/gmd:LI_Source/gmd:description/*[self::gco:CharacterString or self::gmx:Anchor]";
        $array["sourceDescription"] = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:identificationInfo/*/gmd:supplementalInformation/*[self::gco:CharacterString or self::gmx:Anchor]";
        $array["supplementalInformation"] = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:identificationInfo/*/gmd:supplementalInformation/gmd:abstract";
        $array["supplementalInformationAbstract"] = IdfHelper::getNodeValue($node, $xpathExpression);
        $array["operations"] = self::getOperations($node);
        $xpathExpression = "./gmd:identificationInfo/*/srv:containsOperations/srv:SV_OperationMetadata/srv:connectPoint/gmd:CI_OnlineResource/gmd:linkage/gmd:URL";
        $array["operationConnectPoint"] = IdfHelper::getNodeValue($node, $xpathExpression);

        $xpathExpression = "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:identifier/*/gmd:code/*[self::gco:CharacterString or self::gmx:Anchor]";
        $array["identifierCode"] = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:identificationInfo/*/gmd:spatialRepresentationType/gmd:MD_SpatialRepresentationTypeCode/@codeListValue";
        $array["spatialRepresentations"] = IdfHelper::getNodeValueList($node, $xpathExpression);
        $xpathExpression = "./gmd:contentInfo/gmd:MD_FeatureCatalogueDescription/gmd:includedWithDataset/gco:Boolean";
        $array["includedWithDataset"] = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:contentInfo/gmd:MD_FeatureCatalogueDescription/gmd:featureTypes/gco:LocalName";
        $array["featureTypes"] = IdfHelper::getNodeValueList($node, $xpathExpression);
        $array["featureCatalogues"] = self::getFeatureCatalogues($node);
        $array["symbolCatalogues"] = self::getSymbolCatalogues($node);
        $array["vectors"] = self::getVectors($node);

        $xpathExpression = "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:citedResponsibleParty[./gmd:CI_ResponsibleParty/gmd:role/gmd:CI_RoleCode/@codeListValue = 'originator']/gmd:CI_ResponsibleParty/gmd:individualName/*[self::gco:CharacterString or self::gmx:Anchor]";
        $array["literatur_autor"] = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:citedResponsibleParty[./gmd:CI_ResponsibleParty/gmd:role/gmd:CI_RoleCode/@codeListValue = 'resourceProvider']/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:contactInstructions/*[self::gco:CharacterString or self::gmx:Anchor]";
        $array["literatur_loc"] = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:citedResponsibleParty[./gmd:CI_ResponsibleParty/gmd:role/gmd:CI_RoleCode/@codeListValue = 'publisher']/gmd:CI_ResponsibleParty/gmd:individualName/*[self::gco:CharacterString or self::gmx:Anchor]";
        $array["literatur_publisher"] = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:citedResponsibleParty[./gmd:CI_ResponsibleParty/gmd:role/gmd:CI_RoleCode/@codeListValue = 'publisher']/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:city/*[self::gco:CharacterString or self::gmx:Anchor]";
        $array["literatur_publish_loc"] = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:citedResponsibleParty[./gmd:CI_ResponsibleParty/gmd:role/gmd:CI_RoleCode/@codeListValue = 'distribute']/gmd:CI_ResponsibleParty/gmd:organisationName/*[self::gco:CharacterString or self::gmx:Anchor]";
        $array["literatur_publishing"] = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:series/gmd:CI_Series/gmd:name/*[self::gco:CharacterString or self::gmx:Anchor]";
        $array["literatur_publish_in"] = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:editionDate/gco:Date";
        $array["literatur_publish_year"] = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:series/gmd:CI_Series/gmd:issueIdentification/*[self::gco:CharacterString or self::gmx:Anchor]";
        $array["literatur_volume"] = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:series/gmd:CI_Series/gmd:page/*[self::gco:CharacterString or self::gmx:Anchor]";
        $array["literatur_sides"] = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:ISBN/*[self::gco:CharacterString or self::gmx:Anchor]";
        $array["literatur_isbn"] = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:identificationInfo/*/gmd:resourceFormat/gmd:MD_Format/gmd:name/*[self::gco:CharacterString or self::gmx:Anchor]";
        $array["literatur_typ"] = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:lineage/gmd:LI_Lineage/gmd:source/gmd:LI_Source/gmd:description/*[self::gco:CharacterString or self::gmx:Anchor]";
        $array["literatur_base"] = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:otherCitationDetails/*[self::gco:CharacterString or self::gmx:Anchor]";
        $array["literatur_doc_info"] = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:identificationInfo/*/gmd:supplementalInformation/*[self::gco:CharacterString or self::gmx:Anchor]";
        $array["literatur_description"] = IdfHelper::getNodeValue($node, $xpathExpression);

        $xpathExpression = "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:citedResponsibleParty[./gmd:CI_ResponsibleParty/gmd:role/gmd:CI_RoleCode/@codeListValue = 'projectManager']/gmd:CI_ResponsibleParty/gmd:individualName/*[self::gco:CharacterString or self::gmx:Anchor]";
        $array["project_leader"] = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:citedResponsibleParty[./gmd:CI_ResponsibleParty/gmd:role/gmd:CI_RoleCode/@codeListValue = 'projectParticipant']/gmd:CI_ResponsibleParty/gmd:individualName/*[self::gco:CharacterString or self::gmx:Anchor]";
        $array["project_member"] = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:identificationInfo/*/gmd:supplementalInformation/*[self::gco:CharacterString or self::gmx:Anchor]";
        $array["project_description"] = IdfHelper::getNodeValue($node, $xpathExpression);


        $xpathExpression = "./gmd:contentInfo/gmd:MD_FeatureCatalogueDescription/gmd:featureTypes/gco:LocalName";
        $array["data_para"] = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:lineage/gmd:LI_Lineage/gmd:source/gmd:LI_Source/gmd:description/*[self::gco:CharacterString or self::gmx:Anchor]";
        $array["data_base"] = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = "./gmd:identificationInfo/*/gmd:supplementalInformation/*[self::gco:CharacterString or self::gmx:Anchor]";
        $array["data_description"] = IdfHelper::getNodeValue($node, $xpathExpression);

        $metadata["info"] = $array;

        $publish_id = IdfHelper::getNodeValue($node, "./gmd:identificationInfo/*/gmd:resourceConstraints/gmd:MD_SecurityConstraints/gmd:classification/gmd:MD_ClassificationCode/@codeListValue");
        $usage = IdfHelper::getNode($node, "./gmd:identificationInfo/*/gmd:resourceSpecificUsage/gmd:MD_Usage/gmd:specificUsage/*[self::gco:CharacterString or self::gmx:Anchor]");
        $purpose = IdfHelper::getNode($node, "./gmd:identificationInfo/*/gmd:purpose/*[self::gco:CharacterString or self::gmx:Anchor]");
        $legal_basis = IdfHelper::getNodeList($node, "./gmd:identificationInfo/*/gmd:descriptiveKeywords/gmd:MD_Keywords[gmd:thesaurusName/gmd:CI_Citation/gmd:title/*[self::gco:CharacterString or self::gmx:Anchor]='Further legal basis']/gmd:keyword/*[self::gco:CharacterString or self::gmx:Anchor]");
        $export_criteria = IdfHelper::getNodeList($node, "./idf:exportCriteria");
        $language_code = IdfHelper::getNodeList($node, "./gmd:identificationInfo/*/gmd:language/gmd:LanguageCode/@codeListValue");
        $conformity = self::getConformities($node);
        $dataformat = self::getDataformats($node);
        $geodatalink = IdfHelper::getNodeValue($node, "./gmd:identificationInfo/*/gmd:resourceConstraints/gmd:MD_SecurityConstraints/gmd:classification/gmd:MD_ClassificationCode/@codeListValue");
        $media = self::getMedias($node);
        $order_instructions = IdfHelper::getNode($node, "./gmd:distributionInfo/gmd:MD_Distribution/gmd:distributor/gmd:MD_Distributor/gmd:distributionOrderProcess/gmd:MD_StandardOrderProcess/gmd:orderingInstructions/*[self::gco:CharacterString or self::gmx:Anchor]");

        $metadata["info_additional_publish_id"] = $publish_id;
        $metadata["info_additional_usage"] = $usage;
        $metadata["info_additional_purpose"] = $purpose;
        $metadata["info_additional_legal_basis"] = $legal_basis;
        $metadata["info_additional_export_criteria"] = $export_criteria;
        $metadata["info_additional_language_code"] = $language_code;
        $metadata["info_additional_conformity"] = $conformity;
        $metadata["info_additional_dataformat"] = $dataformat;
        $metadata["info_additional_geodatalink"] = $geodatalink;
        $metadata["info_additional_media"] = $media;
        $metadata["info_additional_order_instructions"] = $order_instructions;

        $inspire_themes = IdfHelper::getNodeValueList($node, "./gmd:identificationInfo/*/gmd:descriptiveKeywords[./gmd:MD_Keywords/gmd:thesaurusName/gmd:CI_Citation/gmd:title/*[self::gco:CharacterString or self::gmx:Anchor][contains(text(), 'INSPIRE themes')]]/gmd:MD_Keywords/gmd:keyword/*[self::gco:CharacterString or self::gmx:Anchor]");
        $priority_dataset = IdfHelper::getNodeValueList($node, "./gmd:identificationInfo/*/gmd:descriptiveKeywords[./gmd:MD_Keywords/gmd:thesaurusName/gmd:CI_Citation/gmd:title/*[self::gco:CharacterString or self::gmx:Anchor][contains(text(), 'INSPIRE priority')]]/gmd:MD_Keywords/gmd:keyword/*[self::gco:CharacterString or self::gmx:Anchor]");
        $spatial_scope = IdfHelper::getNodeValueList($node, "./gmd:identificationInfo/*/gmd:descriptiveKeywords[./gmd:MD_Keywords/gmd:thesaurusName/gmd:CI_Citation/gmd:title/*[self::gco:CharacterString or self::gmx:Anchor][contains(text(), 'Spatial scope')]]/gmd:MD_Keywords/gmd:keyword/*[self::gco:CharacterString or self::gmx:Anchor]");
        $gemet_concepts = IdfHelper::getNodeValueList($node, "./gmd:identificationInfo/*/gmd:descriptiveKeywords[./gmd:MD_Keywords/gmd:thesaurusName/gmd:CI_Citation/gmd:title/*[self::gco:CharacterString or self::gmx:Anchor][contains(text(), 'Concepts')]]/gmd:MD_Keywords/gmd:keyword/*[self::gco:CharacterString or self::gmx:Anchor]");
        $adv_group = IdfHelper::getNodeValueList($node, "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:alternateTitle/*[self::gco:CharacterString or self::gmx:Anchor]");
        $invekos = IdfHelper::getNodeValueList($node, "./gmd:identificationInfo/*/gmd:descriptiveKeywords[./gmd:MD_Keywords/gmd:thesaurusName/gmd:CI_Citation/gmd:title/*[self::gco:CharacterString or self::gmx:Anchor][contains(text(), 'IACS Data')]]/gmd:MD_Keywords/gmd:keyword/*[self::gco:CharacterString or self::gmx:Anchor]");
        $topic_category = IdfHelper::getNodeValueList($node, "./gmd:identificationInfo/*/gmd:topicCategory/gmd:MD_TopicCategoryCode");
        $search_terms = IdfHelper::getNodeValueList($node, "./gmd:identificationInfo/*/gmd:descriptiveKeywords
        [./gmd:MD_Keywords/gmd:thesaurusName/gmd:CI_Citation/gmd:title/*
            [self::gco:CharacterString or self::gmx:Anchor]
            [not(contains(text(), 'Further legal basis')) and not(contains(text(), 'INSPIRE themes')) and not(contains(text(), 'INSPIRE priority')) and contains(text(), 'UMTHES')]
            or count(./gmd:MD_Keywords/gmd:thesaurusName) = 0
        ]/gmd:MD_Keywords/gmd:keyword/*[self::gco:CharacterString or self::gmx:Anchor]");

        $metadata["info_keywords_inspire_themes"] = $inspire_themes;
        $metadata["info_keywords_priority_dataset"] = $priority_dataset;
        $metadata["info_keywords_spatial_scope"] = $spatial_scope;
        $metadata["info_keywords_gemet_concepts"] = $gemet_concepts;
        $metadata["info_keywords_adv_group"] = $adv_group;
        $metadata["info_keywords_invekos"] = $invekos;
        $metadata["info_keywords_topic_category"] = $topic_category;
        $metadata["info_keywords_search_terms"] = $search_terms;

    }

    private static function getVectors($node)
    {
        $array = [];
        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:spatialRepresentationInfo/gmd:MD_VectorSpatialRepresentation") ?? [];
        foreach ($tmpNodes as $tmpNode) {
            $item = [];
            $map = array ();
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:topologyLevel/gmd:MD_TopologyLevelCode/@codeListValue");
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "text";
            }
            array_push($item, $map);

            $map = array ();
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:geometricObjects/gmd:MD_GeometricObjects/gmd:geometricObjectType/gmd:MD_GeometricObjectTypeCode/@codeListValue");
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "text";
            }
            array_push($item, $map);

            $map = array ();
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:geometricObjects/gmd:MD_GeometricObjects/gmd:geometricObjectCount/gco:Integer");
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "text";
            }
            array_push($item, $map);

            array_push($array, $item);
        }
        return $array;
    }
    private static function getSymbolCatalogues($node)
    {
        $array = [];
        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:portrayalCatalogueInfo/gmd:MD_PortrayalCatalogueReference/gmd:portrayalCatalogueCitation/gmd:CI_Citation") ?? [];
        foreach ($tmpNodes as $tmpNode) {
            $item = [];
            $map = array ();
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:title/*[self::gco:CharacterString or self::gmx:Anchor]");
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "text";
            }
            array_push($item, $map);

            $map = array ();
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:date/gmd:CI_Date/gmd:date/gco:DateTime");
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "date";
            }
            array_push($item, $map);

            $map = array ();
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:edition/*[self::gco:CharacterString or self::gmx:Anchor]");
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "text";
            }
            array_push($item, $map);

            array_push($array, $item);
        }
        return $array;
    }

    private static function getFeatureCatalogues($node)
    {
        $array = [];
        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:contentInfo/gmd:MD_FeatureCatalogueDescription/gmd:featureCatalogueCitation/gmd:CI_Citation") ?? [];
        foreach ($tmpNodes as $tmpNode) {
            $item = [];
            $map = array ();
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:title/*[self::gco:CharacterString or self::gmx:Anchor]");
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "text";
            }
            array_push($item, $map);

            $map = array ();
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:date/gmd:CI_Date/gmd:date/gco:DateTime");
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "date";
            }
            array_push($item, $map);

            $map = array ();
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:edition/*[self::gco:CharacterString or self::gmx:Anchor]");
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "text";
            }
            array_push($item, $map);

            array_push($array, $item);
        }
        return $array;
    }

    private static function getResolutions($node)
    {
        $denominators = IdfHelper::getNodeValueList($node, "./gmd:identificationInfo/*/gmd:spatialResolution/gmd:MD_Resolution/gmd:equivalentScale/gmd:MD_RepresentativeFraction/gmd:denominator/gco:Integer");
        $dpis = array();
        $meters = array();
        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:identificationInfo/*/gmd:spatialResolution/gmd:MD_Resolution/gmd:distance/gco:Distance[contains(@uom, 'dpi')]");
        foreach ($tmpNodes as $tmpNode) {
            $value = IdfHelper::getNodeValue($tmpNode, ".");
            $unit = IdfHelper::getNodeValue($tmpNode, "./@uom");
            array_push($dpis, $value . " " . $unit);
        }

        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:identificationInfo/*/gmd:spatialResolution/gmd:MD_Resolution/gmd:distance/gco:Distance[contains(@uom, 'meter') or contains(@uom, 'mm') or contains(@uom, 'cm') or contains(@uom, 'm') or contains(@uom, 'km')]");
        foreach ($tmpNodes as $tmpNode) {
            $value = IdfHelper::getNodeValue($tmpNode, ".");
            $unit = IdfHelper::getNodeValue($tmpNode, "./@uom");
            if (strcasecmp($unit, "meter") == 0) {
                $unit = 'm';
            }
            array_push($meters, $value . " " . $unit);
        }
        $map["denominators"] = $denominators;
        $map["dpis"] = $dpis;
        $map["meters"] = $meters;
        return $map;
    }

    private static function getOperations($node)
    {
        $array = [];
        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:identificationInfo/*/srv:containsOperations/srv:SV_OperationMetadata") ?? [];
        foreach ($tmpNodes as $tmpNode) {
            $item = [];
            $map = array ();
            $value = IdfHelper::getNodeValue($tmpNode, "./srv:operationName/*[self::gco:CharacterString or self::gmx:Anchor]");
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "text";
            }
            array_push($item, $map);

            $map = array ();
            $value = IdfHelper::getNodeValue($tmpNode, "./srv:operationDescription/*[self::gco:CharacterString or self::gmx:Anchor]");
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "text";
            }
            array_push($item, $map);

            $map = array ();
            $value = IdfHelper::getNodeValue($tmpNode, "./srv:invocationName/*[self::gco:CharacterString or self::gmx:Anchor]");
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "text";
            }
            array_push($item, $map);

            array_push($array, $item);
        }
        return $array;
    }

    private static function getConformities($node)
    {
        $array = [];
        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:report/gmd:DQ_DomainConsistency") ?? [];
        foreach ($tmpNodes as $tmpNode) {
            $item = [];
            $map = array ();
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:result/gmd:DQ_ConformanceResult/gmd:specification/gmd:CI_Citation/gmd:title/*[self::gco:CharacterString or self::gmx:Anchor]");
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "text";
            }
            array_push($item, $map);

            $map = array ();
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:result/gmd:DQ_ConformanceResult/gmd:specification/gmd:CI_Citation/gmd:date/gmd:CI_Date/gmd:date/gco:Date");
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "date";
            }
            array_push($item, $map);

            $map = array ();
            $tmpSubNode = IdfHelper::getNode($tmpNode, "./gmd:result/gmd:DQ_ConformanceResult/gmd:pass");
            if (!is_null($tmpSubNode)) {
                $value = IdfHelper::getNodeValue($tmpSubNode, "./gco:Boolean");
                if (is_null($value)) {
                    $value = "";
                }
                if (!is_null($value)) {
                    $map["value"] = $value;
                    $map["type"] = "symbol";
                    $map["title"] = "TODO FROM CODELIST 6000";
                }
            }
            array_push($item, $map);

            $map = array ();
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:result/gmd:DQ_ConformanceResult/gmd:explanation/*[self::gco:CharacterString or self::gmx:Anchor]");
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "text";
            }
            array_push($item, $map);
            array_push($array, $item);
        }
        return $array;
    }

    private static function getDataformats($node)
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
                $map = array ();
                if ($name) {
                    $map["value"] = $name;
                    $map["type"] = "text";
                }
                array_push($item, $map);

                $map = array ();
                if ($version) {
                    $map["value"] = $version;
                    $map["type"] = "text";
                }
                array_push($item, $map);

                $map = array ();

                if ($fileDecompression) {
                    $map["value"] = $fileDecompression;
                    $map["type"] = "text";
                }
                array_push($item, $map);

                $map = array ();

                if ($specification) {
                    $map["value"] = $specification;
                    $map["type"] = "text";
                }
                array_push($item, $map);
            }
            if ($item) {
                array_push($array, $item);
            }
        }
        return $array;
    }

    private static function getMedias($node)
    {
        $array = [];
        $tmpNodes = IdfHelper::getNodeList($node, "./gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions[./gmd:offLine]") ?? [];
        foreach ($tmpNodes as $tmpNode) {
            $item = [];
            $map = array ();
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
            array_push($item, $map);

            $map = array ();
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:transferSize/gco:Real");
            if ($value) {
                $map["value"] = $value . " " . $unit;
                $map["type"] = "text";
            }
            array_push($item, $map);

            $map = array ();
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:offLine/gmd:MD_Medium/gmd:mediumNote/*[self::gco:CharacterString or self::gmx:Anchor]");
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "text";
            }
            array_push($item, $map);
            array_push($array, $item);
        }
        return $array;
    }

    private static function getDataQualityRefs($node, &$metadata)
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

        $metadata["data_quality_completenessOmission"] = $completenessOmission;
        $metadata["data_quality_accuracyVertical"] = $accuracyVertical;
        $metadata["data_quality_accuracyGeographic"] = $accuracyGeographic;
        $metadata["data_quality_completenessCommission"] = $completenessCommission;
        $metadata["data_quality_conceptualConsistency"] = $conceptualConsistency;
        $metadata["data_quality_domainConsistency"] = $domainConsistency;
        $metadata["data_quality_formatConsistency"] = $formatConsistency;
        $metadata["data_quality_topologicalConsistency"] = $topologicalConsistency;
        $metadata["data_quality_temporalConsistency"] = $temporalConsistency;
        $metadata["data_quality_thematicClassificationCorrectness"] = $thematicClassificationCorrectness;
        $metadata["data_quality_nonQuantitativeAttributeAccuracy"] = $nonQuantitativeAttributeAccuracy;
        $metadata["data_quality_quantitativeAttributeAccuracy"] = $quantitativeAttributeAccuracy;
        $metadata["data_quality_relativeInternalPositionalAccuracy"] = $relativeInternalPositionalAccuracy;
    }

    private static function getReport($node, $xpath, $dependedDescription, $dependedName)
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

    private static function getReportList($node, $xpath)
    {
        $array = [];
        $tmpNodes = IdfHelper::getNodeList($node, $xpath) ?? [];
        foreach ($tmpNodes as $tmpNode) {
            $name = IdfHelper::getNodeValue($tmpNode, "./gmd:nameOfMeasure/*[self::gco:CharacterString or self::gmx:Anchor]");
            $value = IdfHelper::getNodeValue($tmpNode, "./gmd:result/gmd:DQ_QuantitativeResult/gmd:value/gco:Record");
            $description = IdfHelper::getNodeValue($tmpNode, "./gmd:measureDescription/*[self::gco:CharacterString or self::gmx:Anchor]");
            $item = [];

            $map = array ();
            if ($name) {
                $map["value"] = $name;
                $map["type"] = "text";
            }
            array_push($item, $map);

            $map = array ();
            if ($value) {
                $map["value"] = $value;
                $map["type"] = "text";
            }
            array_push($item, $map);

            $map = array ();
            if ($description) {
                $map["value"] = $description;
                $map["type"] = "text";
            }
            array_push($item, $map);
            array_push($array, $item);
        }
        return $array;
    }

    private static function getMetaInfoRefs($node, $uuid, $dataSourceName, $provider, &$metadata)
    {
        $mod_time = IdfHelper::getNodeValue($node, "./gmd:dateStamp/gco:Date");
        $lang = IdfHelper::getNodeValue($node, "./gmd:language/gmd:LanguageCode/@codeListValue");
        $hierarchy_level = IdfHelper::getNodeValue($node, "./gmd:hierarchyLevel/gmd:MD_ScopeCode/@codeListValue");
        $contact_meta = array (
            "mail" => IdfHelper::getNodeValue($node, "./gmd:contact/*/gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:electronicMailAddress/*[self::gco:CharacterString or self::gmx:Anchor]"),
            "role" => IdfHelper::getNodeValue($node, "./gmd:contact/*/gmd:role/gmd:CI_RoleCode/@codeListValue")
        );
        $plug_data_source_name = "TODO";
        $plug_providers = ["TODO"];

        $metadata["meta_info_uuid"] = $uuid;
        $metadata["meta_info_mod_time"] = $mod_time;
        $metadata["meta_info_lang"] = $lang;
        $metadata["meta_info_hierarchy_level"] = $hierarchy_level;
        $metadata["meta_info_contact_meta"] = $contact_meta;
        $metadata["meta_info_plug_data_source_name"] = $dataSourceName;
        $metadata["meta_info_plug_providers"] = $provider;
    }

}
