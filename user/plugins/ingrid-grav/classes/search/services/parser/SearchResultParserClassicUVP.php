<?php

namespace Grav\Plugin;
use Grav\Common\Plugin;
use Grav\Common\Utils;

class SearchResultParserClassicUVP
{

    public static function parseHits(\stdClass $esHit, string $lang): array
    {
        $uuid = null;
        $type = null;
        $type_name = null;
        $title = null;
        $time = null;
        $datatypes = ElasticsearchHelper::getValueArray($esHit, "datatype");

        if (in_array("address", $datatypes)) {
            $uuid = ElasticsearchHelper::getValue($esHit, "t02_address.adr_id");
            $type = ElasticsearchHelper::getValue($esHit, "t02_address.typ");
            $type_name = isset($type) ? CodelistHelper::getCodelistEntry(["505"], $type, $lang) : null;
            $title = self::getAddressTitle($esHit, $type);
        } else if (in_array("metadata", $datatypes)) {
            $uuid = ElasticsearchHelper::getValue($esHit, "t01_object.obj_id");
            $type = ElasticsearchHelper::getValue($esHit, "t01_object.obj_class");
            $type_name = isset($type) ? CodelistHelper::getCodelistEntry(["8001"], $type, $lang) : null;
            $title = ElasticsearchHelper::getValue($esHit, "title");
            $time = ElasticsearchHelper::getValueTime($esHit, "t01_object.mod_time");
        } else if (in_array("blp", $datatypes)) {
            $title = ElasticsearchHelper::getValue($esHit, "title");
            $additional_html_1 = ElasticsearchHelper::getValue($esHit, "additional_html_1");
        }
        return [
            "uuid" => $uuid,
            "type" => $type,
            "type_name" => $type_name,
            "title" => $title,
            "url" => in_array("www", $datatypes) ? ElasticsearchHelper::getValue($esHit, "url") : null,
            "time" => $time,
            "summary" => ElasticsearchHelper::getValue($esHit, "summary") ?? ElasticsearchHelper::getValue($esHit, "abstract"),
            "datatypes" => $datatypes,
            "partners" => ElasticsearchHelper::getValueArray($esHit, "partner"),
            "addresses" => ElasticsearchHelper::getValueArray($esHit, "uvp_address"),
            "categories" => ElasticsearchHelper::getValueArray($esHit, "uvp_category"),
            "map_bboxes" => ElasticsearchHelper::getBBoxes($esHit, $title),
            "additional_html_1" => $additional_html_1 ?? null,
        ];
    }
}