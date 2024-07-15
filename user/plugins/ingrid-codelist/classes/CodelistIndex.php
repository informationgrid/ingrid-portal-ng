<?php

namespace Grav\Plugin;
use SimpleXMLElement;

class CodelistIndex
{
    public function __construct()
    {
    }

    public static function indexJob(string $codelist_api, string $user, string $pass)
    {
        $opts = [
            "http" => [
                "method" => "GET",
                "header" => "Authorization: Basic " . base64_encode($user . ":" . $pass)
            ]
        ];
        $context = stream_context_create($opts);
        $response = file_get_contents($codelist_api, false, $context);
        $codelists = json_decode($response, true);

        foreach($codelists as $codelist) {
            $id = $codelist["id"];
            self::writeXmlFile($codelist, "user-data://codelists/codelist_" . $id . ".xml");
        }
        $result = array(
            "status" => array(
                "time" => date("d.m.Y h:i:sa", time()),
                "count" => count($codelists)
            ),
            "data" => $codelists
        );
        self::writeJsonFile(json_encode($result, JSON_PRETTY_PRINT), "user-data://codelists/codelists.json");
    }

    private static function writeJsonFile(string $json, string $path)
    {
        $fp = fopen($path, "w");
        fwrite($fp, $json);
        fclose($fp);
    }

    private static function writeXmlFile(array $json, string $path)
    {
        $xml = new SimpleXMLElement('<de.ingrid.codelists.model.CodeList/>');
        self::arrayToXml($json, $xml);
        $fp = fopen($path, "w");
        fwrite($fp, $xml->asXML());
        fclose($fp);
    }

    private static function arrayToXml($array, &$xml)
    {
        foreach ($array as $key => $value) {
            if(is_int($key)){
                $key = "de.ingrid.codelists.model.CodeListEntry";
            }
            if(is_array($value)){
                $label = $xml->addChild($key);
                self::arrayToXml($value, $label);
            }
            else {
                $xml->addChild($key, $value);
            }
        }
    }
}