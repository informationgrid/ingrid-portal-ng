<?php

namespace Grav\Plugin;

class RdfHelper
{

    public static function registerNamespaces(\SimpleXMLElement $node): void
    {
        $node->registerXPathNamespace('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
        $node->registerXPathNamespace('rdfs', 'http://www.w3.org/2000/01/rdf-schema#');
        $node->registerXPathNamespace('owl', 'http://www.w3.org/2002/07/owl#');
        $node->registerXPathNamespace('skos', 'http://www.w3.org/2004/02/skos/core#');
        $node->registerXPathNamespace('dct', 'http://purl.org/dc/terms/');
        $node->registerXPathNamespace('foaf', 'http://xmlns.com/foaf/spec/');
        $node->registerXPathNamespace('void', 'http://rdfs.org/ns/void#');
        $node->registerXPathNamespace('iqvoc', 'http://try.iqvoc.net/schema#');
        $node->registerXPathNamespace('skosxl', 'http://www.w3.org/2008/05/skos-xl#');
        $node->registerXPathNamespace('sns', 'https://sns.uba.de/schema#');
        $node->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1');
        $node->registerXPathNamespace('schema', 'https://sns.uba.de/umthes/schema#');
    }

    public static function getNode(\SimpleXMLElement $node, string $xpath): ?\SimpleXMLElement
    {
        self::registerNamespaces($node);
        $tmpNode = $node->xpath($xpath);
        if ($tmpNode) {
            return $tmpNode[0];
        }
        return null;
    }

    public static function getNodeValue(\SimpleXMLElement $node, string $xpath, ?array $codelist = null, ?string $lang = null): ?string
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

    public static function getNodeList(\SimpleXMLElement $node, string $xpath): array
    {
        self::registerNamespaces($node);
        $tmpNode = $node->xpath($xpath);
        if ($tmpNode) {
            return $tmpNode;
        }
        return [];
    }

    public static function getNodeValueList(\SimpleXMLElement $node, string $xpath, ?array $codelist = null, ?string $lang = null): array
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
                    $array[] = $codelistValue;
                } else {
                    $array[] = $value;
                }
            }
        }
        return $array;
    }

    public static function getNodeValueListCodelistCompare($node, string $xpath, ?array $codelist, ?string $lang, bool $addEqual = true): array
    {
        self::registerNamespaces($node);
        $array = array();
        $tmpNodes = self::getNodeValueList($node, $xpath);
        foreach ($tmpNodes as $tmpNode) {
            if ($tmpNode) {
                $value = (string) $tmpNode;
                if ($codelist && $lang) {
                    $codelistValue = CodelistHelper::getCodelistEntryByCompare($codelist, $value, $lang, $addEqual);
                    if ($codelistValue) {
                        $array[] = $codelistValue;
                    }
                } else {
                    $array[] = $value;
                }
            }
        }
        return $array;
    }

}