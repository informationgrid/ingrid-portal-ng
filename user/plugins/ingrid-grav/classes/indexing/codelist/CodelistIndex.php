<?php

namespace Grav\Plugin;
use Grav\Common\Grav;
use SimpleXMLElement;

class CodelistIndex
{
    public function __construct()
    {
    }

    public static function indexJob(string $api, string $user, string $pass): array
    {
        $log = Grav::instance()['log'];
        $lang = Grav::instance()['language'];
        $log->debug('Start job: Codelist Synchronisation');
        $msg = $lang->translate(['PLUGIN_INGRID_GRAV.CODELIST_API.INDEXING_CODELIST_UNSUCCESS']);
        $status = false;

        $opts = [
            "http" => [
                "method" => "GET",
                "header" => "Authorization: Basic " . base64_encode($user . ":" . $pass)
            ]
        ];
        $context = stream_context_create($opts);
        $response = file_get_contents($api, false, $context);
        $codelists = json_decode($response, true);
        $time = date("d.m.Y H:i", time());
        if ($codelists) {
            foreach($codelists as $codelist) {
                $id = $codelist["id"];
                self::writeXmlFile($codelist, "user-data://codelists", "codelist_" . $id . ".xml");
            }
            $result = array(
                "status" => array(
                    "time" => $time,
                    "count" => count($codelists)
                ),
                "data" => $codelists
            );
            self::writeJsonFile(json_encode($result, JSON_PRETTY_PRINT), "user-data://codelists", "codelists.json");
            $msg = $lang->translate(['PLUGIN_INGRID_GRAV.CODELIST_API.INDEXING_CODELIST_SUCCESS', count($codelists), $time]);
            $status = true;
        } else {
            $log->warn('Codelists could not be synchronized');
            $path = 'user-data://codelists/codelists.json';
            if(file_exists($path)) {
                $response = file_get_contents($path);
                $result = json_decode($response, true);
                $result["status"]["error"] = $time;
                self::writeJsonFile(json_encode($result, JSON_PRETTY_PRINT), "user-data://codelists", "codelists.json");
            }
        }

        $log->debug('Finished job: Codelist Synchronisation');
        return [$status, $msg];
    }

    private static function writeJsonFile(string $json, string $dir, string $file): void
    {
        mkdir($dir);
        $fp = fopen($dir . "/" . $file, "w");
        fwrite($fp, $json);
        fclose($fp);
    }

    private static function writeXmlFile(array $json, string $dir, string $file): void
    {
        $xml = new SimpleXMLElement('<de.ingrid.codelists.model.CodeList/>');
        self::arrayToXml($json, $xml);
        mkdir($dir);
        $fp = fopen($dir . "/" . $file, "w");
        fwrite($fp, $xml->asXML());
        fclose($fp);
    }

    private static function arrayToXml(array $array, SimpleXMLElement &$xml): void
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
