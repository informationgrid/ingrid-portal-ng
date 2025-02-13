<?php

namespace Grav\Plugin;
use Grav\Common\Plugin;
use Grav\Common\Utils;

class ClassicParserISO
{

    public static function parseHits($source, string $lang): array
    {
        $uuid = null;
        $type = null;
        $type_name = null;
        $title = null;
        $time = null;
        $serviceTypes = [];
        $datatypes = self::getValueArray($source, "datatype");

        if (in_array("address", $datatypes)) {
            $uuid = self::getValue($source, "t02_address.adr_id");
            $type = self::getValue($source, "t02_address.typ");
            $type_name = isset($type) ? CodelistHelper::getCodelistEntry(["505"], $type, $lang) : "";
            $title = self::getAddressTitle($source, $type);
        } else if (in_array("metadata", $datatypes)) {
            $uuid = self::getValue($source, "t01_object.obj_id");
            $type = self::getValue($source, "t01_object.obj_class");
            $type_name = isset($type) ? CodelistHelper::getCodelistEntry(["8000"], $type, $lang) : "";
            $title = self::getValue($source, "title");
            $time = self::getTime($source);
        } else if (in_array("www", $datatypes)) {
            $title = self::getValue($source, "title");
        }
        $searchTerms = self::getValueArray($source, "t04_search.searchterm");
        $isInspire = self::getValue($source, "t01_object.is_inspire_relevant");
        if (empty($isInspire)) {
            $isInspire = "N";
        }
        if ($isInspire == "N") {
            if (in_array("inspire", $searchTerms) || in_array("inspireidentifiziert", $searchTerms)) {
                $isInspire = "Y";
            }
        }
        $isOpendata = self::getValue($source, "t01_object.is_open_data");
        if (empty($isOpendata)) {
            $isOpendata = "N";
        }
        if ($isOpendata == "N") {
            if (in_array("opendata", $searchTerms) || in_array("opendataident", $searchTerms)) {
                $isOpendata = "Y";
            }
        }
        $hasAccessContraint = self::getValue($source, "t011_obj_serv.has_access_constraint");
        if (empty($hasAccessContraint)) {
            $hasAccessContraint = "N";
        }
        $servType = self::getFirstValue($source, "t011_obj_serv.type");
        if (!$servType) {
            $servType = self::getFirstValue($source, "refering.object_reference.type");
        }
        $servTypeVersion = self::getFirstValue($source, "t011_obj_serv_version.version_value");
        if (!$servTypeVersion) {
            $servTypeVersion = self::getFirstValue($source, "refering.object_reference.version");
        }
        $obj_serv_type = $servType;
        $capUrl = self::getFirstValue($source, "capabilities_url");
        return [
            "uuid" => $uuid,
            "type" => $type,
            "type_name" => $type_name,
            "title" => $title,
            "url" => in_array("www", $datatypes) ? self::getValue($source, "url") : null,
            "time" => $time,
            "summary" => self::getSummary($source),
            "datatypes" => $datatypes,
            "partners" => self::getValueArray($source, "partner"),
            "searchterms" => $searchTerms,
            "map_bboxes" => self::getBBoxes($source, $title),
            "t011_obj_serv.type" => self::getValue($source, "t011_obj_serv.type"),
            "t011_obj_serv.type_key" => self::getValue($source, "t011_obj_serv.type_key"),
            "license" => self::getLicense($source, $lang),
            "links" => isset($type) ? self::getLinks($source, $type, $servType, $servTypeVersion, $serviceTypes) : [],
            "serviceTypes" => $serviceTypes,
            "additional_html_1" => self::getPreviews($source, "additional_html_1"),
            "isInspire" => !($isInspire == "N"),
            "isOpendata" => !($isOpendata == "N"),
            "hasAccessContraint" => !($hasAccessContraint == "N"),
            "isHVD" => !(self::getValue($source, "is_hvd") === 'false'),
            "obj_serv_type" => $obj_serv_type,
            "mapUrl" => $capUrl ? CapabilitiesHelper::getMapUrl($capUrl, $servTypeVersion, $servType) : null,
            "mapUrlClient" => self::getFirstValue($source, "capabilities_url_with_client"),
            "wkt" => self::getValue($source, "wkt_geo_text"),
            "y1" => self::getValue($source, "y1"),
            "x1" => self::getValue($source, "x1"),
            "y2" => self::getValue($source, "y2"),
            "x2" => self::getValue($source, "x2"),
        ];
    }

    private static function getSummary($value): null|string
    {
        $summary = self::getValue($value, 'summary') ?? self::getValue($value, 'abstract');
        if (!empty($summary) && str_contains($summary, '<')) {
            $doc = new \DomDocument();
            $summary = \mb_convert_encoding($summary, 'HTML-ENTITIES', 'UTF-8');
            $doc->loadHTML($summary, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $summary = $doc->saveHTML();
            while(str_starts_with($summary, '<p>')) {
                $replace = '';
                $find = '<p>';
                $summary = preg_replace("@$find@", $replace, $summary, 1);
                $find = '</p>';
                $summary = preg_replace(strrev("@$find@"), strrev($replace), strrev($summary), 1);
                $summary = strrev($summary);
            }
        }
        return $summary;
    }

    private static function getPreviews($value, string $type): array
    {
        $array = [];

        $previews = self::getValueArray($value, $type);
        foreach ($previews as $preview) {
            $url = preg_replace("/.* src='/i", "", $preview);
            $url = preg_replace("/'.*/i", "", $url);

            $title = preg_replace("/.* title='/i", "", $preview);
            $title = preg_replace("/'.*/i", "", $title);

            $img = $preview;
            $array[] = [
                "url" => $url,
                "title" => $title,
                "img" => $img,
            ];
        }

        return $array;
    }

    private static function getAddressTitle($value, string $type): string
    {
        $title = self::getValue($value, "title");
        if ($type == "2" or $type == "3") {
            $title = "";
            $title = $title . (property_exists($value, "t02_address.firstname") ? self::getValue($value, "t02_address.firstname") . " " : "");
            $title = $title . (property_exists($value, "t02_address.lastname") ? self::getValue($value, "t02_address.lastname") . " " : "");
        }
        if (property_exists($value, "t02_address.parents.title")) {
            $parents = self::getValue($value, "t02_address.parents.title");
            if (is_string($parents)) {
                $title = self::getValue($value, "t02_address.parents.title") . ', ' . $title;
            } else {
                foreach ($parents as $parent) {
                    $title = $parent . ', ' . $title;
                }
            }
        }
        return $title;
    }

    private static function getLicense($value, string $lang): mixed
    {
        $licenseKey = self::getFirstValue($value, "object_use_constraint.license_key");
        $licenseValue = self::getFirstValue($value, "object_use_constraint.license_value");

        if ($licenseKey || $licenseValue) {
            if ($licenseKey) {
                $item = json_decode(CodelistHelper::getCodelistEntryData(["6500"], $licenseKey));
                if ($item) {
                    return $item;
                }
                $item = CodelistHelper::getCodelistEntry(["6500"], $licenseKey, $lang);
                if ($item) {
                    return array(
                        "name" => $item
                    );
                }
            }
            if ($licenseValue) {
                if (str_starts_with($licenseValue, '{')) {
                    return json_decode($licenseValue);
                } else {
                    return array(
                        "name" => $licenseValue
                    );
                }
            }
        }
        return null;
    }


    private static function getLinks($value, string $type, null|string $serviceTyp, null|string $serviceTypeVersion, array &$serviceTypes): array
    {
        $referenceAllUUID = [];
        $referenceAllName = [];
        $referenceAllClass = [];
        $referenceAllClassName = [];
        $referenceAllServiceVersion = [];
        $referenceAllServiceType = [];

        $array = array ();
        $referingObjRefUUID = self::getValueArray($value, "refering.object_reference.obj_uuid");
        $referingObjRefName = self::getValueArray($value, "refering.object_reference.obj_name");
        $referingObjRefClass = self::getValueArray($value, "refering.object_reference.obj_class");
        $referingObjRefType = self::getValueArray($value, "refering.object_reference.type");
        $referingObjRefVersion = self::getValueArray($value, "refering.object_reference.version");

        foreach ($referingObjRefUUID as $count => $objUuid) {
            if (str_starts_with($objUuid, "http")) {
                $array[] = [
                    "url" => $objUuid,
                    "title" => !empty($referingObjRefName[$count]) ? $referingObjRefName[$count] : $objUuid,
                    "kind" => "other",
                ];
            } else {
                if (!in_array($objUuid, $referenceAllUUID)) {
                    $referenceAllUUID[] = $objUuid;
                    $referenceAllName[] = $referingObjRefName[$count];
                    $referenceAllClass[] = $referingObjRefClass[$count];
                    $referenceAllClassName[] = CodelistHelper::getCodelistEntry(['8000'], $referingObjRefClass[$count], 'de');
                    if ($referingObjRefClass[$count] == "3") {
                        $referenceAllServiceVersion[] = count($referingObjRefVersion) > $count ? $referingObjRefVersion[$count] : "";
                    } else {
                        $referenceAllServiceVersion[] = "";
                    }
                    if (count($referingObjRefType) > $count) {
                        $referenceAllServiceType[] = $referingObjRefType[$count];
                        if (!in_array($referingObjRefType[$count], $serviceTypes)) {
                            $serviceTypes[] = $referingObjRefType[$count];
                        }
                    } else {
                        $referenceAllServiceType[] = "";
                    }
                }
            }
        }

        $objRefUUID = self::getValueArray($value, "object_reference.obj_uuid");
        $objRefName = self::getValueArray($value, "object_reference.obj_name");
        $objRefClass = self::getValueArray($value, "object_reference.obj_class");
        $objRefType = self::getValueArray($value, "object_reference.type");
        $objRefVersion = self::getValueArray($value, "object_reference.version");

        foreach ($objRefUUID as $count => $objUuid) {
            if (str_starts_with($objUuid, "http")) {
                $array[] = [
                    "url" => $objUuid,
                    "title" => !empty($objRefName[$count]) ? $objRefName[$count] : $objUuid,
                    "kind" => "other",
                ];
            } else {
                if(!empty($objRefName[$count])) {
                    if (!in_array($objUuid, $referenceAllUUID)) {
                        $referenceAllUUID[] = $objUuid;
                        $referenceAllName[] = $objRefName[$count];
                        $referenceAllClass[] = $objRefClass[$count];
                        $referenceAllClassName[] = CodelistHelper::getCodelistEntry(['8000'], $objRefClass[$count], 'de');
                        if ($objRefClass[$count] == "3") {
                            $referenceAllServiceVersion[] = count($objRefVersion) > $count ? $objRefVersion[$count] : "";
                        } else {
                            $referenceAllServiceVersion[] = "";
                        }
                        if (count($objRefType) > $count) {
                            $referenceAllServiceType[] = $objRefType[$count];
                            if (!in_array($objRefType[$count], $serviceTypes)) {
                                $serviceTypes[] = $objRefType[$count];
                            }
                        } else {
                            $referenceAllServiceType[] = "";
                        }
                    }
                }
            }
        }

        $urlReferenceLink = self::getValueArray($value, "t017_url_ref.url_link");
        $urlReferenceContent = self::getValueArray($value, "t017_url_ref.content");
        $urlReferenceSpecialRef = self::getValueArray($value, "t017_url_ref.special_ref");
        $urlReferenceDatatype = self::getValueArray($value, "t017_url_ref.datatype");

        foreach ($urlReferenceLink as $count => $url) {
            $format = !empty($urlReferenceSpecialRef[$count]) ? $urlReferenceSpecialRef[$count] : null;
            $kind = "other";
            if ($format == "9990") {
                $kind = "download";
            } else if ($format == "3600") {
                $kind = "reference";
            }
            $array[] = [
                "url" => $url,
                "title" => !empty($urlReferenceContent[$count]) ? $urlReferenceContent[$count] : $url,
                "serviceType" => $format == "9900" && count($urlReferenceDatatype) > $count ? $urlReferenceDatatype[$count] : "",
                "type" => $format == "3600" ? "1" : null,
                "typeName" => $format == "3600" ? CodelistHelper::getCodelistEntry(['8000'], "1", 'de') : null,
                "kind" => $kind,
            ];
            if (count($urlReferenceDatatype) > $count) {
                if (!in_array($urlReferenceDatatype[$count], $serviceTypes)) {
                    $serviceTypes[] = $urlReferenceDatatype[$count];
                }
            }
        }

        foreach($referenceAllUUID as $count => $uuid) {
            $array[] = [
                "uuid" => $uuid,
                "title" => $referenceAllName[$count],
                "type" => $referenceAllClass[$count],
                "typeName" => $referenceAllClassName[$count],
                "serviceType" => CapabilitiesHelper::getHitServiceType($referenceAllServiceVersion[$count], $referenceAllServiceType[$count]),
                "kind" => "reference",
            ];
        }

        // URL des Zugangs
        if ($type == "3") {
            $connectPointLink = self::getFirstValue($value, "capabilities_url");
            if (empty($connectPointLink)) {
                $connectPointLink = self::getFirstValue($value, "t011_obj_serv_op_connpoint.connect_point");
            }
            if ($connectPointLink) {
                $capURL = CapabilitiesHelper::getCapabilitiesUrl($connectPointLink, $serviceTypeVersion, $serviceTyp);
                $array[] = [
                    "url" => $capURL,
                    "title" => $capURL,
                    "kind" => "access",
                ];
            }
        } else if ($type == "6") {
            $connectPointLink = self::getValueArray($value, "t011_obj_serv_url.url");
            $connectPointLinkName = self::getValueArray($value, "t011_obj_serv_url.name");
            foreach ($connectPointLink as $count => $url) {
                $array[] = [
                    "url" => $url,
                    "title" => !empty($connectPointLinkName[$count]) ? $connectPointLinkName[$count] : $url,
                    "kind" => "access",
                ];
            }
        }
        return Utils::sortArrayByKey($array, "title", SORT_ASC);
    }

    private static function getTime($value): array
    {
        return [
            "type" => self::getValue($value, "t01_object.time_type"),
            "t0" => self::getValueTime($value, "t0"),
            "t1" => self::getValueTime($value, "t1"),
            "t2" => self::getValueTime($value, "t2"),
        ];
    }

    private static function getBBoxes($value, null|string $title): array
    {
        $array = array();
        if (property_exists($value, "x1")) {
            $x1s = self::toArray(self::getValue($value, "x1"));
            $y1s = self::toArray(self::getValue($value, "y1"));
            $x2s = self::toArray(self::getValue($value, "x2"));
            $y2s = self::toArray(self::getValue($value, "y2"));
            $locations = self::toArray(self::getValue($value, "location"));

            $count = 0;
            foreach ($x1s as $x1) {
                $array[] = [
                    "title" => $locations[$count] ?? $title,
                    "westBoundLongitude" => (float) $x1s[$count],
                    "southBoundLatitude" => (float) $y1s[$count],
                    "eastBoundLongitude" => (float) $x2s[$count],
                    "northBoundLatitude" => (float) $y2s[$count],
                ];
                $count++;
            }
        }
        return $array;
    }

    private static function getObjServType($node)
    {
        $array = array();
        return $array;
    }

    private static function toArray($value): array
    {
        if (isset($value)) {
            if (gettype($value) == "array") return $value;
            return array($value);
        }
        return [];
    }

    private static function getValue($value, string $key): mixed
    {
        if (property_exists($value, $key)) {
            $tmpValue = $value->$key;
            if (is_string($tmpValue)) {
                return trim($value->$key);
            }
            return $tmpValue;
        }
        return null;
    }

    private static function getValueTime($value, string $key): null|string
    {
        if (property_exists($value, $key)) {
            $time = trim($value->$key);
            return date("d.m.Y", strtotime(substr($time,0,8)));
        }
        return null;
    }

    private static function getValueArray($value, string $key): array
    {
        return self::toArray(self::getValue($value, $key)) ?? [];
    }

    private static function getFirstValue($value, string $key): mixed
    {
        $array = self::getValueArray($value, $key);
        return $array[0] ?? null;
    }

}