<?php

namespace Grav\Plugin;
use Grav\Common\Plugin;
use Grav\Common\Utils;

class ClassicParserUVP
{

    public static function parseHits($source, string $lang): array
    {
        $uuid = null;
        $type = null;
        $type_name = null;
        $title = null;
        $time = null;
        $datatypes = self::getValueArray($source, "datatype");

        if (in_array("address", $datatypes)) {
            $uuid = self::getValue($source, "t02_address.adr_id");
            $type = self::getValue($source, "t02_address.typ");
            $type_name = isset($type) ? CodelistHelper::getCodelistEntry(["505"], $type, $lang) : null;
            $title = self::getAddressTitle($source, $type);
        } else if (in_array("metadata", $datatypes)) {
            $uuid = self::getValue($source, "t01_object.obj_id");
            $type = self::getValue($source, "t01_object.obj_class");
            $type_name = isset($type) ? CodelistHelper::getCodelistEntry(["8001"], $type, $lang) : null;
            $title = self::getValue($source, "title");
            $time = self::getValueTime($source, "t01_object.mod_time");
        } else if (in_array("blp", $datatypes)) {
            $title = self::getValue($source, "title");
            $additional_html_1 = self::getValue($source, "additional_html_1");
        }
        return [
            "uuid" => $uuid,
            "type" => $type,
            "type_name" => $type_name,
            "title" => $title,
            "url" => in_array("www", $datatypes) ? self::getValue($source, "url") : null,
            "time" => $time,
            "summary" => self::getValue($source, "summary") ?? self::getValue($source, "abstract"),
            "datatypes" => $datatypes,
            "partners" => self::getValueArray($source, "partner"),
            "addresses" => self::getValueArray($source, "uvp_address"),
            "categories" => self::getValueArray($source, "uvp_category"),
            "map_bboxes" => self::getBBoxes($source, $title),
            "additional_html_1" => $additional_html_1 ?? null,
        ];
    }

    private static function getBBoxes($value, null|string $title): array
    {
        $array = [];
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

    private static function toArray($value): array
    {
        if ($value) {
            if (gettype($value) == "array") return $value;
            return array($value);
        }
        return [];
    }

    private static function getValue($value, string $key): mixed
    {
        if (property_exists($value, $key)) {
            return $value -> $key;
        }
        return null;
    }

    private static function getValueTime($value, string $key): null|string
    {
        if (property_exists($value, $key)) {
            $time = $value -> $key;
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