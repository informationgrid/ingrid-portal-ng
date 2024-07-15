<?php

namespace Grav\Plugin;

class SearchResponseTransformerClassic
{
    public static function parseHits(array $hits): array
    {
        return array_map(self::parseHit(...), $hits);
    }

    /**
     * @param object $aggregations
     * @param FacetConfig[] $config
     * @return FacetResult[]
     */
    public static function parseAggregations(object $aggregations, array $config): array
    {
        $result = array();

        foreach ($config as $facetConfig) {
            $items = array();
            if (property_exists((object)$facetConfig, 'queries')) {
                foreach ($facetConfig['queries'] as $key => $query) {
                    $items[] = new FacetItem($key, ((array)$aggregations)[$key]->doc_count);
                }
            } else {
                foreach (((array)$aggregations)[$facetConfig['id']]->buckets as $bucket) {
                    $items[] = new FacetItem($bucket->key, $bucket->doc_count);
                }
            }
            $result[] = new FacetResult($facetConfig['id'], $items);
        }

        return $result;
    }

    private static function parseHit($esHit)
    {
        $value = $esHit->_source;

        //$isFolder = (boolean) self::getValue($value, "isfolder") ?? true;
        $datatypes = self::getValueArray($value, "datatype");

        $hit = array();
        if (in_array("address", $datatypes)) {
            $hit["uuid"] = self::getValue($value, "t02_address.adr_id");
            $hit["type"] = self::getValue($value, "t02_address.typ");
            $hit["type_name"] = CodelistHelper::getCodelistEntry(["505"], $hit["type"], "de");
            $hit["title"] = self::getAddressTitle($value, $hit["type"]);
        } else if (in_array("metadata", $datatypes)) {
            $hit["uuid"] = self::getValue($value, "t01_object.obj_id");
            $hit["type"] = self::getValue($value, "t01_object.obj_class");
            $hit["type_name"] = $hit["type"] ? CodelistHelper::getCodelistEntry(["8000"], $hit["type"], "de") : null;
            $hit["title"] = self::getValue($value, "title");
            $hit["time"] = self::getTime($value);
        } else if (in_array("www", $datatypes)) {
        }
        $hit["summary"] = self::getValue($value, "summary") ?? self::getValue($value, "abstract");
        $hit["datatypes"] = $datatypes;
        $hit["partners"] = self::getValueArray($value, "partner");
        $hit["searchterms"] = self::getValueArray($value, "t04_search.searchterm");
        $hit["map_bboxes"] = self::getBBoxes($value);
        $hit["obj_serv_type"] = self::getObjServType($value);
        $hit["t011_obj_serv.type"] = self::getValue($value, "t011_obj_serv.type");
        $hit["t011_obj_serv.type_key"] = self::getValue($value, "t011_obj_serv.type_key");
        $hit["license"] = self::getLicense($value);
        $hit["links"] = self::getLinks($value);
        $hit["additional_html_1"] = self::getPreviews($value, "additional_html_1");
        $hit["mapUrl"] = self::getFirstValue($value, "capabilities_url");
        $hit["mapUrlClient"] = self::getFirstValue($value, "capabilities_url_with_client");
        $hit["isInspire"] = self::getValue($value, "t01_object.is_inspire_relevant");
        $hit["isOpendata"] = self::getValue($value, "t01_object.is_open_data");
        $hit["hasAccessContraint"] = self::getValue($value, "t011_obj_serv.has_access_constraint");
        return $hit;
    }

    private static function getPreviews($value, $type) {
        $array = array ();

        $previews = self::getValueArray($value, "additional_html_1");
        foreach ($previews as $preview) {
            $url = preg_replace("/.* src='/i", "", $preview);
            $url = preg_replace("/'.*/i", "", $url);

            $title = preg_replace("/.* title='/i", "", $preview);
            $title = preg_replace("/'.*/i", "", $title);

            $img = $preview;
            array_push($array, array (
                "url" => $url,
                "title" => $title,
                "img" => $img,
            ));
        }

        return $array;
    }
    private static function getAddressTitle($value, $type) {
        $title = self::getValue($value, "title");
        if ($type == "2") {
            $title = "";
            $title = $title . (property_exists($value, "title2") ? self::getValue($value, "title2") . ", " : "");
            $title = $title . (property_exists($value, "t02_address.address_value") ? self::getValue($value, "t02_address.address_value") . " " : "");
            $title = $title . (property_exists($value, "t02_address.title") ? self::getValue($value, "t02_address.title") . " " : "");
            $title = $title . (property_exists($value, "t02_address.firstname") ? self::getValue($value, "t02_address.firstname") . " " : "");
            $title = $title . (property_exists($value, "t02_address.lastname") ? self::getValue($value, "t02_address.lastname") . " " : "");
        } else if ($type == "3") {
            $title = "";
            $title = $title . (property_exists($value, "title2") ? self::getValue($value, "title2") . ", " : "");
            $title = $title . (property_exists($value, "t02_address.address_value") ? self::getValue($value, "t02_address.address_value") . " " : "");
            $title = $title . (property_exists($value, "t02_address.title") ? self::getValue($value, "t02_address.title") . " " : "");
            $title = $title . (property_exists($value, "t02_address.firstname") ? self::getValue($value, "t02_address.firstname") . " " : "");
            $title = $title . (property_exists($value, "t02_address.lastname") ? self::getValue($value, "t02_address.lastname") . " " : "");
        }
        return $title;
    }

    private static function getLicense($value)
    {
        $licenseKey = self::getFirstValue($value, "object_use_constraint.license_key");
        $licenseValue = self::getFirstValue($value, "object_use_constraint.license_value");

        if ($licenseKey || $licenseValue) {
            if ($licenseKey) {
                $item = json_decode(CodelistHelper::getCodelistEntryData(["6500"], $licenseKey));
                if ($item) {
                    return $item;
                }
                $item = CodelistHelper::getCodelistEntry(["6500"], $licenseKey, "de");
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


    private static function getLinks($value)
    {
        $referenceAllUUID = [];
        $referenceAllName = [];
        $referenceAllClass = [];

        $array = array ();
        $referingObjRefUUID = self::getValueArray($value, "refering.object_reference.obj_uuid");
        $referingObjRefName = self::getValueArray($value, "refering.object_reference.obj_name");
        $referingObjRefClass = self::getValueArray($value, "refering.object_reference.obj_class");
        $referingObjRefType = self::getValueArray($value, "refering.object_reference.type");
        $referingObjRefVersion = self::getValueArray($value, "refering.object_reference.version");

        $count = 0;
        foreach ($referingObjRefUUID as $objUuid) {
            if(str_starts_with($objUuid, "http")) {
                $item = array ();
                $item["url"] = $objUuid;
                $item["title"] = !empty($referingObjRefName[$count]) ? $referingObjRefName[$count] : $objUuid;
                $item["format"] = "";
                $item["kind"] = "other";
                array_push($array, $item);
            } else {
                if (!in_array($objUuid, $referenceAllUUID)) {
                    array_push($referenceAllUUID, $objUuid);
                    array_push($referenceAllName, $referingObjRefName[$count]);
                    array_push($referenceAllClass, $referingObjRefClass[$count]);
                }
            }
            $count++;
        }

        $objRefUUID = self::getValueArray($value, "object_reference.obj_uuid");
        $objRefName = self::getValueArray($value, "object_reference.obj_name");
        $objRefClass = self::getValueArray($value, "object_reference.obj_class");
        $objRefType = self::getValueArray($value, "object_reference.type");
        $objRefVersion = self::getValueArray($value, "object_reference.version");

        $count = 0;
        foreach ($objRefUUID as $objUuid) {
            if(str_starts_with($objUuid, "http")) {
                $item = array ();
                $item["url"] = $objUuid;
                $item["title"] = !empty($objRefName[$count]) ? $objRefName[$count] : $objUuid;
                $item["format"] = "";
                $item["kind"] = "other";
                array_push($array, $item);
            } else {
                if(!empty($objRefName[$count])) {
                    if (!in_array($objUuid, $referenceAllUUID)) {
                        array_push($referenceAllUUID, $objUuid);
                        array_push($referenceAllName, $objRefName[$count]);
                        array_push($referenceAllClass, $objRefClass[$count]);
                    }
                }
            }
            $count++;
        }

        $urlReferenceLink = self::getValueArray($value, "t017_url_ref.url_link");
        $urlReferenceContent = self::getValueArray($value, "t017_url_ref.content");
        $urlReferenceSpecialRef = self::getValueArray($value, "t017_url_ref.special_ref");
        $urlReferenceDatatype = self::getValueArray($value, "t017_url_ref.datatype");

        $count = 0;
        foreach ($urlReferenceLink as $url) {
            $item = array ();
            $item["url"] = $url;
            $item["title"] = !empty($urlReferenceContent[$count]) ? $urlReferenceContent[$count] : $url;
            $item["format"] = !empty($urlReferenceSpecialRef[$count]) ? $urlReferenceSpecialRef[$count] : null;
            if ($item["format"] == "9990") {
                $item["kind"] = "download";
            } else if ($item["format"] == "3600") {
                $item["kind"] = "reference";
            } else {
                $item["kind"] = "other";
            }
            array_push($array, $item);
            $count++;
        }

        $count = 0;
        foreach($referenceAllUUID as $uuid) {
            $item = array ();
            $item["uuid"] = $uuid;
            $item["title"] = $referenceAllName[$count];
            $item["type"] = $referenceAllClass[$count];
            $item["kind"] = "reference";
            array_push($array, $item);
            $count++;
        }

        $connectPointLink = self::getValueArray($value, "t011_obj_serv_op_connpoint.connect_point");
        foreach ($connectPointLink as $url) {
            $item = array ();
            $item["url"] = $url;
            $item["title"] = $url;
            $item["format"] = "";
            $item["kind"] = "access";
            array_push($array, $item);
         }
        return $array;
    }

    private static function getTime($value)
    {
        $map = array ();
        $map["type"] = self::getValue($value, "t01_object.time_type");
        $map["t0"] = self::getValueTime($value, "t0");
        $map["t1"] = self::getValueTime($value, "t1");
        $map["t2"] = self::getValueTime($value, "t2");
        return $map;
    }

    private static function getBBoxes($value)
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
                $map = array ();
                $map["title"] = $locations[$count] ?? "";
                $map["westBoundLongitude"] = $x1s[$count];
                $map["southBoundLatitude"] = (float) $y1s[$count];
                $map["eastBoundLongitude"] = (float) $x2s[$count];
                $map["northBoundLatitude"] = (float) $y2s[$count];
                array_push($array, $map);
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
        if ($value) {
            if (gettype($value) == "array") return $value;
            return array($value);
        }
        return [];
    }

    private static function getValue($value, $key)
    {
        if (property_exists($value, $key)) {
            return $value -> $key;
        }
        return null;
    }

    private static function getValueTime($value, $key)
    {
        if (property_exists($value, $key)) {
            $time = $value -> $key;
            return date("d.m.Y", strtotime(substr($time,0,8)));
        }
        return null;
    }

    private static function getValueArray($value, $key)
    {
        return self::toArray(self::getValue($value, $key)) ?? [];
    }

    private static function getFirstValue($value, $key)
    {
        $array = self::getValueArray($value, $key);
        return $array[0] ?? null;
    }

}
