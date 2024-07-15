<?php

namespace Grav\Plugin;

class IdfHelper
{


    public function __construct()
    {

    }


    public static function registerNamespaces($node) {
        $node->registerXPathNamespace('idf', 'http://www.portalu.de/IDF/1.0');
        $node->registerXPathNamespace('gco', 'http://www.isotc211.org/2005/gco');
        $node->registerXPathNamespace('gmd', 'http://www.isotc211.org/2005/gmd');
        $node->registerXPathNamespace('gml', 'http://www.opengis.net/gml/3.2');
        $node->registerXPathNamespace('gmx', 'http://www.isotc211.org/2005/gmx');
        $node->registerXPathNamespace('gts', 'http://www.isotc211.org/2005/gts');
        $node->registerXPathNamespace('srv', 'http://www.isotc211.org/2005/srv');
        $node->registerXPathNamespace('xlink', 'http://www.w3.org/1999/xlink');
        $node->registerXPathNamespace('xsi', 'http://www.w3.org/2001/XMLSchema-instance');
    }

    public static function getNode($node, string $xpath)
    {
        self::registerNamespaces($node);
        $tmpNode = $node->xpath($xpath);
        if ($tmpNode) {
            return $tmpNode[0];
        }
        return null;
    }

    public static function getNodeValue($node, string $xpath, array $codelist = null, string $lang = null)
    {
        self::registerNamespaces($node);
        $tmpNode = $node->xpath($xpath);
        if ($tmpNode) {
            $value = (string) $tmpNode[0];
            if ($codelist && $lang) {
                $codelistValue = CodelistHelper::getCodelistEntry($codelist, $value, $lang);
                if ($codelistValue == null) {
                    $codelistValue = CodelistHelper::getCodelistEntryByIso($codelist, $value, $lang);
                }
                if ($codelistValue == null) {
                    $codelistValue = CodelistHelper::getCodelistEntryByData($codelist, $value, $lang);
                }
                if ($codelistValue == null) {
                    $codelistValue = $value;
                }
                return $codelistValue;
            } else {
                return $value;
            }
        }
        return null;
    }

    public static function getNodeList($node, string $xpath)
    {
        self::registerNamespaces($node);
        $tmpNode = $node->xpath($xpath);
        if ($tmpNode) {
            return $tmpNode;
        }
        return [];
    }

    public static function getNodeValueList($node, string $xpath, array $codelist = null, string $lang = null)
    {
        self::registerNamespaces($node);
        $array = array();
        $tmpNodes = $node->xpath($xpath);
        foreach ($tmpNodes as $tmpNode) {
            if ($tmpNode) {
                $value = (string) $tmpNode;
                if ($codelist && $lang) {
                    $codelistValue = CodelistHelper::getCodelistEntry($codelist, $value, $lang);
                    if ($codelistValue == null) {
                        $codelistValue = CodelistHelper::getCodelistEntryByIso($codelist, $value, $lang);
                    }
                    if ($codelistValue == null) {
                        $codelistValue = CodelistHelper::getCodelistEntryByData($codelist, $value, $lang);
                    }
                    if ($codelistValue == null) {
                        $codelistValue = $value;
                    }
                    array_push($array, $codelistValue);
                } else {
                    array_push($array, $value);
                }
            }
        }
        return $array;
    }

    public static function getNodeValueListCodelistCompare($node, string $xpath, array $codelist = null, string $lang = null, bool $addEqual = true)
    {
        self::registerNamespaces($node);
        $array = array();
        $tmpNodes = $node->xpath($xpath);
        foreach ($tmpNodes as $tmpNode) {
            if ($tmpNode) {
                $value = (string) $tmpNode;
                if ($codelist && $lang) {
                    $codelistValue = CodelistHelper::getCodelistEntryByCompare($codelist, $value, $lang, $addEqual);
                    if ($codelistValue) {
                        array_push($array, $codelistValue);
                    }
                } else {
                    array_push($array, $value);
                }
            }
        }
        return $array;
    }

}