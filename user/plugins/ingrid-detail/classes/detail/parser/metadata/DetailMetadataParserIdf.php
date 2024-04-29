<?php

namespace Grav\Plugin;

class DetailMetadataParserIdf
{

    public static function parse($node, $uuid){
        echo "<script>console.log('InGrid Detail parse metadata with " . $uuid . "');</script>";

        $metadata = new DetailMetadata();
        $metadata->setTitle(IdfHelper::getNode($node, "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:title/gco:CharacterString/text()"));
        $metadata->setType(self::getType($node));
        $metadata->setSummary(IdfHelper::getNode($node, "./idf:abstract/gco:CharacterString/text() | ./gmd:abstract/gco:CharacterString/text()"));
        $metadata->setAlternateTitle(IdfHelper::getNode($node, "./gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:alternateTitle/gco:CharacterString/text()"));
        $metadata->setTimeRefs(self::getTimeRefs($node));
        $metadata->setMapRefs(self::getMapRefs($node));
        $metadata->setLinkRefs(self::getLinkRefs($node));
        $metadata->setUseRefs(self::getUseRefs($node));
        $metadata->setContactRefs(self::getContactRefs($node));
        $metadata->setInfoRefs(self::getInfoRefs($node));
        $metadata->setMetaInfoRefs(self::getMetaInfoRefs($node));
        return $metadata;
    }

    public static function getType($node)
    {
        $hierachyLevel = IdfHelper::getNode($node, "./gmd:hierarchyLevel/gmd:MD_ScopeCode/@codeListValue");
        $hierachyLevelName = IdfHelper::getNode($node, "./gmd:hierarchyLevelName/gco:CharacterString/text()");

        if (strcasecmp($hierachyLevel, "service") > -1){
            return "3";
        } else if (strcasecmp($hierachyLevel, "application") > -1){
            return "6";
        } else if (strcasecmp($hierachyLevelName, "job") > -1 && strcasecmp($hierachyLevel, "nonGeographicDataset") > -1){
            return "0";
        } else if (strcasecmp($hierachyLevelName, "document") > -1 && strcasecmp($hierachyLevel, "nonGeographicDataset") > -1){
            return "2";
        } else if (strcasecmp($hierachyLevelName, "project") > -1 && strcasecmp($hierachyLevel, "nonGeographicDataset") > -1){
            return "4";
        } else if (strcasecmp($hierachyLevelName, "database") > -1 && strcasecmp($hierachyLevel, "nonGeographicDataset") > -1){
            return "5";
        } else if (strcasecmp($hierachyLevel, "dataset") > -1 || strcasecmp($hierachyLevel, "series") > -1){
            return "1";
        } else if (strcasecmp($hierachyLevel, "tile") > -1){
            // tile should be mapped to "Geoinformation/Karte" explicitly, see INGRID-2225
            return "1";
        } else {
            // Default to "Geoinformation/Karte", see INGRID-2225
            return "1";
        }
    }

    public static function getTimeRefs($node)
    {
        $array = array();
        ## Durch die Ressource abgedeckte Zeitspanne
        $tmpValue = IdfHelper::getNode($node, "./gmd:identificationInfo/*/*/gmd:EX_Extent/gmd:temporalElement/gmd:EX_TemporalExtent/gmd:extent/gml:TimeInstant/gml:timePosition/text()");
        if (!is_null($tmpValue)) {
            $array["atTime"] = $tmpValue;
        }

        $tmpValue = IdfHelper::getNode($node, "./gmd:identificationInfo/*/*/gmd:EX_Extent/gmd:temporalElement/gmd:EX_TemporalExtent/gmd:extent/gml:TimePeriod/gml:beginPosition/text()");
        if (!is_null($tmpValue)) {
            $array["beginTime"] = $tmpValue;
        }

        $tmpValue = IdfHelper::getNode($node, "./gmd:identificationInfo/*/*/gmd:EX_Extent/gmd:temporalElement/gmd:EX_TemporalExtent/gmd:extent/gml:TimePeriod/gml:endPosition/text()");
        if (!is_null($tmpValue)) {
            $array["endTime"] = $tmpValue;
        }

        $tmpValue = IdfHelper::getNode($node, "./gmd:identificationInfo/*/*/gmd:EX_Extent/gmd:temporalElement/gmd:EX_TemporalExtent/gmd:extent/gml:TimePeriod/gml:endPosition/@indeterminatePosition");
        if (!is_null($tmpValue)) {
            $array["fromType"] = $tmpValue;
        }

        ## Status
        $tmpValue = IdfHelper::getNode($node, "./*/*/gmd:status/gmd:MD_ProgressCode/@codeListValue");
        if (!is_null($tmpValue)) {
            $array["status"] = $tmpValue;
        }

        ## Periodizität
        $tmpValue = IdfHelper::getNode($node, "./*/*/gmd:resourceMaintenance/gmd:MD_MaintenanceInformation/gmd:maintenanceAndUpdateFrequency/gmd:MD_MaintenanceFrequencyCode/@codeListValue");
        if (!is_null($tmpValue)) {
            $array["period"] = $tmpValue;
        }

        ## Intervall der Erhebung
        $tmpValue = IdfHelper::getNode($node, "./*/*/gmd:resourceMaintenance/gmd:MD_MaintenanceInformation/gmd:userDefinedMaintenanceFrequency/gts:TM_PeriodDuration/text()");
        if (!is_null($tmpValue)) {
            $array["interval"] = $tmpValue;
        }

        ## Erläuterung zum Zeitbezug
        $tmpValue = IdfHelper::getNode($node, "./*/*/gmd:resourceMaintenance/gmd:MD_MaintenanceInformation/gmd:maintenanceNote/gco:CharacterString/text()");
        if (!is_null($tmpValue)) {
            $array["descr"] = $tmpValue;
        }

        ## Zeitbezug der Ressource
        $tmpValue = IdfHelper::getNode($node, "./*/*/gmd:citation/gmd:CI_Citation/gmd:date/gmd:CI_Date[./gmd:date]");
        if (!is_null($tmpValue)) {
            $tmpArray = array();
            $tmpValue = IdfHelper::getNode($node, "./*/*/gmd:citation/gmd:CI_Citation/gmd:date/gmd:CI_Date[gmd:dateType/gmd:CI_DateTypeCode/@codeListValue = 'creation']/gmd:date/gco:DateTime/text()");
            if (!is_null($tmpValue)) {
                $tmpArray["creation"] = $tmpValue;
            }
            $tmpValue = IdfHelper::getNode($node, "./*/*/gmd:citation/gmd:CI_Citation/gmd:date/gmd:CI_Date[gmd:dateType/gmd:CI_DateTypeCode/@codeListValue = 'publication']/gmd:date/gco:DateTime/text()");
            if (!is_null($tmpValue)) {
                $tmpArray["publication"] = $tmpValue;
            }
            $tmpValue = IdfHelper::getNode($node, "./*/*/gmd:citation/gmd:CI_Citation/gmd:date/gmd:CI_Date[gmd:dateType/gmd:CI_DateTypeCode/@codeListValue = 'revision']/gmd:date/gco:DateTime/text()");
            if (!is_null($tmpValue)) {
                $tmpArray["revision"] = $tmpValue;
            }
            $array["timeDate"] = $tmpArray;
        }

        $tmpValue = IdfHelper::getNode($node, "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:report/gmd:DQ_AccuracyOfATimeMeasurement/gmd:result/gmd:DQ_QuantitativeResul/gmd:value/gco:Record/text()");
        if (!is_null($tmpValue)) {
            $array["measureValue"] = $tmpValue;
        }

        $tmpValue = IdfHelper::getNode($node, "./gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:report/gmd:DQ_AccuracyOfATimeMeasurement/gmd:result/gmd:DQ_QuantitativeResul/gmd:valueUnit/gml:UnitDefinition/gml:catalogSymbol/text()");
        if (!is_null($tmpValue)) {
            $array["measureUnit"] = $tmpValue;
        }
        return $array;
    }

    public static function getMapRefs($node)
    {
        $array = array();

        return $array;
    }

    public static function getLinkRefs($node)
    {
        $array = array();

        return $array;
    }

    public static function getUseRefs($node)
    {
        $array = array();

        return $array;
    }

    public static function getContactRefs($node)
    {
        $array = [];
        $nodes = IdfHelper::getNodeList($node, "./gmd:identificationInfo/*/gmd:pointOfContact/*");
        
        foreach ($nodes as $tmpNode) {
            $uuid = "";
            $type = "";
            $role = "";
            $roleNode = IdfHelper::getNode($tmpNode, "./gmd:role/gmd:CI_RoleCode");
            if ($roleNode) {
                $role = $roleNode->attributes()->codeListValue; 
            }
            $addresses = [];
            $tmpAddresses = IdfHelper::getNodeList($tmpNode, "./idf:hierarchyParty");
            if(is_null($tmpAddresses)) {
                $tmpAddresses = IdfHelper::getNodeList($tmpNode, "./");
            }
            
            foreach ($tmpAddresses as $tmpAddress) {
                $uuid = $tmpAddress->attributes()->uuid;
                $type = IdfHelper::getNode($tmpAddress, "./idf:addressType");
                $title = IdfHelper::getNode($tmpAddress, "./idf:addressIndividualName | ./gmd:individualName/gco:CharacterString/text()");
                if (is_null($title)) {
                    $title = IdfHelper::getNode($tmpAddress, "./idf:addressOrganisationName | ./gmd:organisationName/gco:CharacterString/text()");
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
            $tmpStreets = IdfHelper::getNodeList($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:deliveryPoint/gco:CharacterString/text()") ?? [];
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
            $postcode = IdfHelper::getNode($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:postalCode/gco:CharacterString/text()");
            $city = IdfHelper::getNode($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:city/gco:CharacterString/text()");
            $country = IdfHelper::getNode($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:country/gco:CharacterString/text()");
            $mail = IdfHelper::getNode($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:electronicMailAddress/gco:CharacterString/text()");
            $phone = IdfHelper::getNode($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:phone/gmd:CI_Telephone/gmd:voice/gco:CharacterString/text()");
            $facsimile = IdfHelper::getNode($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:phone/gmd:CI_Telephone/gmd:facsimile/gco:CharacterString/text()");
            $url = IdfHelper::getNode($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:onlineResource/gmd:CI_OnlineResource/gmd:linkage/gmd:URL");
            $service_time = IdfHelper::getNode($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:hoursOfService/gco:CharacterString/text()");

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
        return $array;
    }

    public static function getInfoRefs($node)
    {
        $array = array();

        return $array;
    }

    public static function getMetaInfoRefs($node)
    {
        $array = array();

        return $array;
    }

}
